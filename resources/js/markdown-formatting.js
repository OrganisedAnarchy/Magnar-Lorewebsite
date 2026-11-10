import MarkdownIt from 'markdown-it';

const markdown = new MarkdownIt({ html: false, linkify: true });

export function renderMarkdown(value) {
    return markdown.render(value);
}

export function formatSelection(text, from, to, format) {
    const selected = text.slice(from, to);
    const wrappers = {
        bold: ['**', '**'],
        italic: ['*', '*'],
        code: ['`', '`'],
        link: ['[', '](https://)'],
        wikilink: ['[[', ']]'],
    };

    if (wrappers[format]) {
        const [before, after] = wrappers[format];
        return { from, to, insert: before + selected + after, anchor: from + before.length, head: from + before.length + selected.length };
    }

    const prefixes = { heading: '## ', quote: '> ', bullet: '- ', numbered: '1. ' };
    const prefix = prefixes[format];
    if (!prefix) {
        throw new Error(`Unknown Markdown format: ${format}`);
    }

    const lineStart = from === 0 ? 0 : text.lastIndexOf('\n', from - 1) + 1;
    const lineEnd = text.indexOf('\n', to > from && text[to - 1] === '\n' ? to - 1 : to);
    const end = lineEnd === -1 ? text.length : lineEnd;
    const lines = text.slice(lineStart, end).split('\n');
    const insert = lines.map((line) => prefix + line).join('\n');

    return { from: lineStart, to: end, insert, anchor: lineStart, head: lineStart + insert.length };
}
