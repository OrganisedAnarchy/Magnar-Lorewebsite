import MarkdownIt from 'markdown-it';
import { findHeading, headingSlug } from './markdown-links.js';

const markdown = new MarkdownIt({ html: false, linkify: true });

// Give headings ids so that single-bracket internal links ([Heading]) can point at them.
markdown.core.ruler.after('block', 'heading_anchors', (state) => {
    state.env.headings = [];
    state.tokens.forEach((token, index) => {
        if (token.type !== 'heading_open') {
            return;
        }
        const text = state.tokens[index + 1].content;
        const slug = headingSlug(text);
        if (slug) {
            token.attrSet('id', slug);
            state.env.headings.push({ text });
        }
    });
});

markdown.inline.ruler.before('link', 'internal_link', (state, silent) => {
    if (state.src[state.pos] !== '[' || /[[\]!\\]/.test(state.src[state.pos - 1] ?? '')) {
        return false;
    }
    const match = /^\[([^[\]\n]+)\](?![[\](:])/.exec(state.src.slice(state.pos, state.posMax));
    const heading = match && !match[1].startsWith('^') ? findHeading(state.env.headings ?? [], match[1]) : null;
    if (!heading) {
        return false;
    }

    if (!silent) {
        const open = state.push('link_open', 'a', 1);
        open.attrs = [['href', `#${headingSlug(heading.text)}`]];
        state.push('text', '', 0).content = match[1];
        state.push('link_close', 'a', -1);
    }
    state.pos += match[0].length;

    return true;
});

export function renderMarkdown(value) {
    return markdown.render(value);
}

// Embedded images are written {{https://…/image.png|alt text}}.
export const IMAGE_EMBED = /\{\{(https?:\/\/[^|{}\s]+)\|([^{}\n]*)\}\}/g;

markdown.inline.ruler.before('image', 'lore_image', (state, silent) => {
    if (!state.src.startsWith('{{', state.pos)) {
        return false;
    }
    const match = new RegExp(`^${IMAGE_EMBED.source}`).exec(state.src.slice(state.pos, state.posMax));
    const src = match ? state.md.normalizeLink(match[1]) : '';
    if (!match || !state.md.validateLink(src)) {
        return false;
    }

    if (!silent) {
        const alt = match[2].trim();
        const token = state.push('image', 'img', 0);
        token.attrs = [['src', src], ['alt', '']];
        token.content = alt;
        token.children = [Object.assign(new state.Token('text', '', 0), { content: alt })];
    }
    state.pos += match[0].length;

    return true;
});

/** The text to insert for an uploaded image; characters that would break the syntax are stripped from the alt text. */
export function imageEmbed(url, altText) {
    return `{{${url}|${altText.replace(/[|{}\r\n]+/g, ' ').replace(/\s+/g, ' ').trim()}}}`;
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
