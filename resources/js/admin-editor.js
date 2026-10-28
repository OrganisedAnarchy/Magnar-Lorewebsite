import { EditorView, ViewPlugin, Decoration, drawSelection, keymap } from '@codemirror/view';
import { EditorState, RangeSetBuilder } from '@codemirror/state';
import { markdown } from '@codemirror/lang-markdown';
import { syntaxTree } from '@codemirror/language';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';

const hidden = Decoration.replace({});
const wikilink = Decoration.mark({ class: 'cm-wikilink' });
const markNodes = new Set(['HeaderMark', 'EmphasisMark', 'CodeMark', 'QuoteMark', 'StrikethroughMark']);

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
        for (const match of text.matchAll(/\[\[[^\]\n]+\]\]/g)) {
            items.push({ from: from + match.index, to: from + match.index + match[0].length, deco: wikilink });
        }
    }

    items.sort((a, b) => a.from - b.from || a.deco.startSide - b.deco.startSide);
    for (const item of items) {
        builder.add(item.from, item.to, item.deco);
    }

    return builder.finish();
}

const livePreviewPlugin = ViewPlugin.fromClass(
    class {
        constructor(view) {
            this.decorations = livePreview(view);
        }

        update(update) {
            if (update.docChanged || update.viewportChanged || update.selectionSet) {
                this.decorations = livePreview(update.view);
            }
        }
    },
    { decorations: (plugin) => plugin.decorations },
);

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
        '.cm-wikilink': { color: '#cdb8ff', textDecoration: 'underline' },
        '.cm-strong': { fontWeight: '700' },
        '.cm-emphasis': { fontStyle: 'italic' },
        '.cm-selectionBackground, &.cm-focused .cm-selectionBackground': { backgroundColor: '#4a3d85' },
    },
    { dark: true },
);

function mount(textarea) {
    const holder = document.createElement('div');
    textarea.after(holder);
    textarea.style.display = 'none';

    new EditorView({
        parent: holder,
        state: EditorState.create({
            doc: textarea.value,
            extensions: [
                history(),
                drawSelection(),
                keymap.of([...defaultKeymap, ...historyKeymap]),
                markdown(),
                EditorView.lineWrapping,
                livePreviewPlugin,
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
