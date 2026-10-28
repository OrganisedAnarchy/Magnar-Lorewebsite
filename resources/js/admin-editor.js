import { EditorView, ViewPlugin, Decoration, drawSelection, keymap } from '@codemirror/view';
import { EditorState, RangeSetBuilder, StateEffect } from '@codemirror/state';
import { markdown } from '@codemirror/lang-markdown';
import { syntaxTree } from '@codemirror/language';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { acceptCompletion, autocompletion, completionKeymap } from '@codemirror/autocomplete';

const hidden = Decoration.replace({});
const wikilink = Decoration.mark({ class: 'cm-wikilink' });
const wikilinkRaw = Decoration.mark({ class: 'cm-wikilink-raw' });
const wikilinkMissing = Decoration.mark({ class: 'cm-wikilink cm-wikilink-missing' });
const linksChecked = StateEffect.define();
// Whether a link resolves to an existing entry, keyed by title and category.
const linkStatus = new Map();
const linkKey = (link) => `${link.title.toLowerCase()}@${link.category.toLowerCase()}`;
const markNodes = new Set(['HeaderMark', 'EmphasisMark', 'CodeMark', 'QuoteMark', 'StrikethroughMark']);

// Syntax: [[Title]], [[Title|Alias]], [[Title@Category]] or [[Title|Alias@Category]].
// Offsets are relative to the text between the brackets.
function parseWikilink(inner) {
    const at = inner.lastIndexOf('@');
    const left = at >= 0 ? inner.slice(0, at) : inner;
    const category = at >= 0 ? inner.slice(at + 1).trim() : '';
    const pipe = left.indexOf('|');
    const title = (pipe >= 0 ? left.slice(0, pipe) : left).trim();
    const hasAlias = pipe >= 0 && left.slice(pipe + 1).trim() !== '';

    return {
        title,
        category,
        displayStart: hasAlias ? pipe + 1 : 0,
        displayEnd: hasAlias || pipe < 0 ? left.length : pipe,
    };
}

// Obsidian-style live preview: syntax marks are hidden except on lines the cursor is touching.
function livePreview(view) {
    const builder = new RangeSetBuilder();
    const active = new Set();
    for (const range of view.state.selection.ranges) {
        const first = view.state.doc.lineAt(range.from).number;
        const last = view.state.doc.lineAt(range.to).number;
        for (let line = first; line <= last; line++) {
            active.add(line);
        }
    }

    const items = [];
    for (const { from, to } of view.visibleRanges) {
        syntaxTree(view.state).iterate({
            from,
            to,
            enter(node) {
                const line = view.state.doc.lineAt(node.from);
                const heading = /^(?:ATX|Setext)Heading(\d)$/.exec(node.name);
                if (heading && node.from === line.from) {
                    items.push({ from: line.from, to: line.from, deco: Decoration.line({ class: `cm-h${heading[1]}` }) });
                }
                if (node.name === 'Blockquote' && node.from === line.from) {
                    items.push({ from: line.from, to: line.from, deco: Decoration.line({ class: 'cm-quote' }) });
                }
                if (markNodes.has(node.name) && !active.has(line.number)) {
                    let end = node.to;
                    if (node.name === 'HeaderMark' || node.name === 'QuoteMark') {
                        end = Math.min(line.to, end + 1);
                    }
                    items.push({ from: node.from, to: end, deco: hidden });
                }
            },
        });
    }

    for (const { from, to } of view.visibleRanges) {
        const text = view.state.doc.sliceString(from, to);
        for (const match of text.matchAll(/\[\[([^\]\n]+)\]\]/g)) {
            const start = from + match.index;
            const end = start + match[0].length;

            const editing = view.state.selection.ranges.some((range) => range.from <= end && range.to >= start);
            if (editing) {
                items.push({ from: start, to: end, deco: wikilinkRaw });
                continue;
            }

            // Out of focus the link reads like an HTML link: only the alias (or else the title) is shown.
            const parsed = parseWikilink(match[1]);
            const innerStart = start + 2;
            const displayFrom = innerStart + parsed.displayStart;
            const displayTo = innerStart + parsed.displayEnd;
            items.push({ from: start, to: displayFrom, deco: hidden });
            items.push({ from: displayFrom, to: displayTo, deco: linkStatus.get(linkKey(parsed)) === false ? wikilinkMissing : wikilink });
            items.push({ from: displayTo, to: end, deco: hidden });
        }
    }

    items.sort((a, b) => a.from - b.from || a.deco.startSide - b.deco.startSide);
    for (const item of items) {
        if (item.to > item.from || item.deco.spec.class?.startsWith('cm-h') || item.deco.spec.class === 'cm-quote') {
            builder.add(item.from, item.to, item.deco);
        }
    }

    return builder.finish();
}

const livePreviewPlugin = ViewPlugin.fromClass(
    class {
        constructor(view) {
            this.decorations = livePreview(view);
        }

        update(update) {
            if (update.docChanged || update.viewportChanged || update.selectionSet || update.transactions.some((tr) => tr.effects.some((e) => e.is(linksChecked)))) {
                this.decorations = livePreview(update.view);
            }
        }
    },
    { decorations: (plugin) => plugin.decorations },
);

// Asks the server (debounced) whether the visible links point at existing entries, then redraws.
function linkChecker(checkUrl, csrf) {
    return ViewPlugin.fromClass(
        class {
            constructor(view) {
                this.view = view;
                this.timer = null;
                this.schedule();
            }

            update(update) {
                if (update.docChanged || update.viewportChanged) {
                    this.schedule();
                }
            }

            destroy() {
                clearTimeout(this.timer);
            }

            schedule() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.check(), 350);
            }

            async check() {
                const pending = new Map();
                for (const { from, to } of this.view.visibleRanges) {
                    for (const match of this.view.state.doc.sliceString(from, to).matchAll(/\[\[([^\]\n]+)\]\]/g)) {
                        const link = parseWikilink(match[1]);
                        const key = linkKey(link);
                        if (link.title && !linkStatus.has(key)) {
                            pending.set(key, { title: link.title, category: link.category });
                        }
                    }
                }
                if (pending.size === 0) {
                    return;
                }

                try {
                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ links: [...pending.values()] }),
                    });
                    if (!response.ok) {
                        return;
                    }
                    const { results } = await response.json();
                    [...pending.keys()].forEach((key, index) => linkStatus.set(key, Boolean(results[index])));
                    this.view.dispatch({ effects: linksChecked.of(null) });
                } catch {
                    // Leave the links unstyled as "unknown"; the next edit retries.
                }
            }
        },
    );
}

const theme = EditorView.theme(
    {
        '&': { backgroundColor: '#1f1b2b', color: '#e8e4f0', border: '1px solid #3a3450', borderRadius: '4px', minHeight: '20rem' },
        '&.cm-focused': { outline: '2px solid #6b4fd8' },
        '.cm-content': { padding: '.75rem', caretColor: '#fff', fontFamily: 'system-ui, sans-serif', lineHeight: '1.6' },
        '.cm-cursor': { borderLeftColor: '#fff' },
        '.cm-scroller': { minHeight: '20rem' },
        '.cm-h1': { fontSize: '1.8em', fontWeight: '700' },
        '.cm-h2': { fontSize: '1.5em', fontWeight: '700' },
        '.cm-h3': { fontSize: '1.25em', fontWeight: '700' },
        '.cm-h4, .cm-h5, .cm-h6': { fontWeight: '700' },
        '.cm-quote': { borderLeft: '3px solid #6b4fd8', paddingLeft: '.75rem', color: '#b9b2cc' },
        '.cm-wikilink': { color: '#8ab4ff', textDecoration: 'underline', cursor: 'pointer' },
        '.cm-wikilink-raw': { color: '#cdb8ff' },
        '.cm-wikilink-missing': { color: '#7d778c', textDecorationStyle: 'dotted' },
        '.cm-strong': { fontWeight: '700' },
        '.cm-emphasis': { fontStyle: 'italic' },
        '.cm-selectionBackground, &.cm-focused .cm-selectionBackground': { backgroundColor: '#4a3d85' },
        '.cm-tooltip.cm-tooltip-autocomplete': { backgroundColor: '#1f1b2b', border: '1px solid #3a3450' },
        '.cm-tooltip-autocomplete ul li[aria-selected]': { backgroundColor: '#6b4fd8', color: '#fff' },
        '.cm-completionDetail': { marginLeft: '.75rem', opacity: '.7', fontStyle: 'normal' },
    },
    { dark: true },
);

function wikilinkAt(view, pos) {
    const line = view.state.doc.lineAt(pos);
    for (const match of line.text.matchAll(/\[\[([^\]\n]+)\]\]/g)) {
        const start = line.from + match.index;
        if (pos >= start && pos <= start + match[0].length) {
            const { title, category } = parseWikilink(match[1]);

            return { title, category };
        }
    }

    return null;
}

function mount(textarea) {
    const { searchUrl, resolveUrl, checkUrl } = textarea.dataset;
    const categories = JSON.parse(textarea.dataset.categories ?? '[]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    for (const link of JSON.parse(textarea.dataset.linkStatuses ?? '[]')) {
        linkStatus.set(linkKey(link), link.exists);
    }
    // Insert the chosen text and close the link if it isn't closed already.
    const applyTitle = (view, completion, from, to) => {
        const closed = view.state.doc.sliceString(to, to + 2) === ']]';
        const insert = closed ? completion.label : `${completion.label}]]`;
        view.dispatch({ changes: { from, to, insert }, selection: { anchor: from + completion.label.length + 2 }, userEvent: 'input.complete' });
    };

    const wikilinkSource = async (context) => {
        const category = context.matchBefore(/\[\[[^\]\n]*@[^\]\n@]*/);
        if (category) {
            const typed = category.text.slice(category.text.lastIndexOf('@') + 1).toLowerCase();
            return {
                from: category.to - typed.length,
                filter: false,
                options: categories
                    .filter((label) => label.toLowerCase().includes(typed))
                    .map((label) => ({ label, apply: (view, completion, from, to) => applyTitle(view, completion, from, to) })),
            };
        }

        const title = context.matchBefore(/\[\[[^\]\n|@]*/);
        if (!title) {
            return null;
        }

        const query = title.text.slice(2).trim();
        if (query.length < 3) {
            return null;
        }

        const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
        if (!response.ok || context.aborted) {
            return null;
        }

        const results = await response.json();

        return {
            from: title.from + 2,
            filter: false,
            options: results.map((entry) => ({ label: entry.title, detail: entry.category, apply: applyTitle })),
        };
    };

    const openLink = async (link, view) => {
        const tab = window.open('', '_blank');
        try {
            const response = await fetch(resolveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(link),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message ?? 'Could not open that link.');
            }
            tab.location = data.url;
            linkStatus.set(linkKey(link), true);
            view.dispatch({ effects: linksChecked.of(null) });
        } catch (error) {
            tab?.close();
            window.alert(error.message);
        }
    };

    const clickToOpen = EditorView.domEventHandlers({
        mousedown(event, view) {
            if (!(event.ctrlKey || event.metaKey)) {
                return false;
            }
            const pos = view.posAtCoords({ x: event.clientX, y: event.clientY });
            const link = pos === null ? null : wikilinkAt(view, pos);
            if (!link) {
                return false;
            }
            event.preventDefault();
            openLink(link, view);

            return true;
        },
    });

    const holder = document.createElement('div');
    textarea.after(holder);
    textarea.style.display = 'none';

    new EditorView({
        parent: holder,
        state: EditorState.create({
            doc: textarea.value,
            extensions: [
                history(),
                autocompletion({ override: [wikilinkSource], activateOnTyping: true }),
                clickToOpen,
                drawSelection(),
                keymap.of([{ key: 'Tab', run: acceptCompletion }, ...completionKeymap, ...defaultKeymap, ...historyKeymap]),
                markdown(),
                EditorView.lineWrapping,
                livePreviewPlugin,
                linkChecker(checkUrl, csrf),
                theme,
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        textarea.value = update.state.doc.toString();
                    }
                }),
            ],
        }),
    });
}

document.querySelectorAll('textarea[data-markdown-editor]').forEach(mount);
