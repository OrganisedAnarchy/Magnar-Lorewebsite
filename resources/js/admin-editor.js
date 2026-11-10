import { EditorView, ViewPlugin, Decoration, drawSelection, keymap, placeholder as editorPlaceholder } from '@codemirror/view';
import { EditorState, RangeSetBuilder, StateEffect } from '@codemirror/state';
import { markdown } from '@codemirror/lang-markdown';
import { HighlightStyle, syntaxHighlighting, syntaxTree } from '@codemirror/language';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { acceptCompletion, autocompletion, completionKeymap } from '@codemirror/autocomplete';
import { tags } from '@lezer/highlight';
import { extractHeadings, findHeading, findInternalLinks, parseWikilink } from './markdown-links.js';

const hidden = Decoration.replace({});
const wikilink = Decoration.mark({ class: 'cm-wikilink' });
const wikilinkRaw = Decoration.mark({ class: 'cm-wikilink-raw' });
const wikilinkMissing = Decoration.mark({ class: 'cm-wikilink cm-wikilink-missing' });
const internalLink = Decoration.mark({ class: 'cm-wikilink cm-internal-link' });
const linksChecked = StateEffect.define();
// Whether a link resolves to an existing entry, keyed by title and category.
const linkStatus = new Map();
const linkKey = (link) => `${link.title.toLowerCase()}@${link.category.toLowerCase()}`;
const markNodes = new Set(['HeaderMark', 'EmphasisMark', 'CodeMark', 'QuoteMark', 'StrikethroughMark']);

// Internal links ([Heading]) are looked up against the whole document, so cache them per document version.
const internalLinkCache = new WeakMap();
function internalLinks(doc) {
    let links = internalLinkCache.get(doc);
    if (!links) {
        links = findInternalLinks(doc.toString());
        internalLinkCache.set(doc, links);
    }

    return links;
}

function goToHeading(view, name) {
    const heading = findHeading(extractHeadings(view.state.doc.toString()), name);
    if (!heading) {
        return false;
    }
    view.dispatch({
        selection: { anchor: heading.from },
        effects: EditorView.scrollIntoView(heading.from, { y: 'start', yMargin: 16 }),
    });

    return true;
}

// Set once an editor has scrolled to the heading named in the page's URL hash, so only one editor does.
let hashHandled = false;
function followLocationHash(view) {
    if (hashHandled || !window.location.hash) {
        return;
    }
    let name;
    try {
        name = decodeURIComponent(window.location.hash.slice(1));
    } catch {
        return;
    }
    hashHandled = goToHeading(view, name);
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

    for (const link of internalLinks(view.state.doc)) {
        if (!view.visibleRanges.some(({ from, to }) => link.from >= from && link.to <= to)) {
            continue;
        }
        const editing = view.state.selection.ranges.some((range) => range.from <= link.to && range.to >= link.from);
        if (editing) {
            items.push({ from: link.from, to: link.to, deco: wikilinkRaw });
            continue;
        }
        items.push({ from: link.from, to: link.from + 1, deco: hidden });
        items.push({ from: link.from + 1, to: link.to - 1, deco: internalLink });
        items.push({ from: link.to - 1, to: link.to, deco: hidden });
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

// Editor styling lives in resources/scss/admin.scss; this only flags the theme as dark.
const theme = EditorView.darkTheme.of(true);
const markdownHighlight = HighlightStyle.define([
    { tag: tags.strong, class: 'cm-strong' },
    { tag: tags.emphasis, class: 'cm-emphasis' },
]);

function wikilinkAt(view, pos) {
    const line = view.state.doc.lineAt(pos);
    for (const match of line.text.matchAll(/\[\[([^\]\n]+)\]\]/g)) {
        const start = line.from + match.index;
        if (pos >= start && pos <= start + match[0].length) {
            const { title, heading, category } = parseWikilink(match[1]);

            return { title, heading, category, to: start + match[0].length };
        }
    }

    const internal = internalLinks(view.state.doc).find((link) => pos >= link.from && pos <= link.to);

    return internal ? { title: '', heading: internal.heading.text, category: '', to: internal.to } : null;
}

// Small popup next to the click asking which type a new link should be created as.
function pickCategory(x, y, options) {
    return new Promise((resolve) => {
        const menu = document.createElement('div');
        menu.className = 'category-picker';
        menu.style.left = `${x}px`;
        menu.style.top = `${y}px`;
        const search = document.createElement('input');
        search.type = 'search';
        search.placeholder = 'Create this link as… (type to search)';
        search.className = 'category-picker-search';
        const list = document.createElement('div');
        menu.append(search, list);

        const close = (value) => {
            document.removeEventListener('mousedown', outside, true);
            menu.remove();
            resolve(value);
        };
        const outside = (event) => {
            if (!menu.contains(event.target)) {
                close(null);
            }
        };

        let matches = [];
        let active = 0;
        const highlight = () => {
            [...list.children].forEach((button, index) => {
                button.classList.toggle('active', index === active);
            });
        };
        const render = () => {
            const term = search.value.trim().toLowerCase();
            matches = options.filter((label) => label.toLowerCase().includes(term));
            active = Math.min(active, Math.max(matches.length - 1, 0));
            list.replaceChildren(...matches.map((label, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.className = 'category-picker-option';
                button.onmouseenter = () => { active = index; highlight(); };
                button.onmousedown = (event) => {
                    event.preventDefault();
                    close(label);
                };
                return button;
            }));
            highlight();
        };

        search.addEventListener('input', () => { active = 0; render(); });
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close(null);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (matches[active]) {
                    close(matches[active]);
                }
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                active = (active + (event.key === 'ArrowDown' ? 1 : -1) + matches.length) % Math.max(matches.length, 1);
                render();
            }
        });

        render();
        document.body.append(menu);
        document.addEventListener('mousedown', outside, true);
        search.focus();
    });
}

// Typing "[" over a selection wraps it (selection is kept, so a second "[" nests);
// typing "[" right after another "[" completes the pair as "[[]]".
const bracketHelper = EditorView.inputHandler.of((view, from, to, text) => {
    if (text !== '[') {
        return false;
    }

    const { state } = view;
    const range = state.selection.main;

    if (!range.empty && from === range.from && to === range.to) {
        view.dispatch({
            changes: [
                { from: range.from, insert: '[' },
                { from: range.to, insert: ']' },
            ],
            selection: { anchor: range.from + 1, head: range.to + 1 },
            userEvent: 'input',
        });

        return true;
    }

    if (from === to && from > 0 && state.sliceDoc(from - 1, from) === '[') {
        view.dispatch({
            changes: { from, insert: '[]]' },
            selection: { anchor: from + 1 },
            userEvent: 'input',
        });

        return true;
    }

    return false;
});

export function createMarkdownEditor(parent, { doc, onChange, searchUrl, resolveUrl, checkUrl, categories = [], linkStatuses = [], label, placeholder = '', maxLength = null, required = false }) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    for (const link of linkStatuses) {
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

        const title = context.matchBefore(/\[\[[^\]\n|@#]*/);
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

    const openLink = async (link, view, point) => {
        // No page named: the link points at a heading in this same document.
        if (!link.title) {
            if (link.heading) {
                goToHeading(view, link.heading);
            }

            return;
        }

        // An uncategorised link that doesn't exist yet: ask what to create, then write it into the link.
        if (!link.category && linkStatus.get(linkKey(link)) !== true) {
            const chosen = await pickCategory(point.x, point.y, categories);
            if (!chosen) {
                return;
            }
            view.dispatch({ changes: { from: link.to - 2, insert: `@${chosen}` }, userEvent: 'input' });
            link = { title: link.title, heading: link.heading, category: chosen };
        }

        const tab = window.open('', '_blank');
        try {
            const response = await fetch(resolveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title: link.title, heading: link.heading || null, category: link.category }),
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message ?? 'Could not open that link.');
            }
            if (tab) {
                tab.location = data.url;
            } else {
                window.open(data.url, '_blank');
            }
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
            openLink(link, view, { x: event.clientX, y: event.clientY });

            return true;
        },
    });

    const view = new EditorView({
        parent,
        state: EditorState.create({
            doc,
            extensions: [
                history(),
                autocompletion({ override: [wikilinkSource], activateOnTyping: true }),
                clickToOpen,
                bracketHelper,
                drawSelection(),
                keymap.of([{ key: 'Tab', run: acceptCompletion }, ...completionKeymap, ...defaultKeymap, ...historyKeymap]),
                markdown(),
                syntaxHighlighting(markdownHighlight),
                editorPlaceholder(placeholder),
                EditorView.lineWrapping,
                EditorView.contentAttributes.of({ 'aria-label': label, 'aria-multiline': 'true', 'aria-required': String(required) }),
                EditorState.transactionFilter.of((transaction) => {
                    if (maxLength !== null && transaction.docChanged && transaction.newDoc.length > maxLength && transaction.newDoc.length > transaction.startState.doc.length) {
                        return [];
                    }
                    return transaction;
                }),
                livePreviewPlugin,
                linkChecker(checkUrl, csrf),
                theme,
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        onChange(update.state.doc.toString());
                    }
                }),
            ],
        }),
    });

    requestAnimationFrame(() => followLocationHash(view));

    return view;
}
