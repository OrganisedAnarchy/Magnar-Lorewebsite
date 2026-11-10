// Syntax: [[Title]], [[Title|Alias]], [[Title@Category]], [[Title#Heading]] or any combination,
// e.g. [[Title#Heading|Alias@Category]]. Offsets are relative to the text between the brackets.
export function parseWikilink(inner) {
    const at = inner.lastIndexOf('@');
    const left = at >= 0 ? inner.slice(0, at) : inner;
    const category = at >= 0 ? inner.slice(at + 1).trim() : '';
    const pipe = left.indexOf('|');
    const hasAlias = pipe >= 0 && left.slice(pipe + 1).trim() !== '';
    const target = pipe >= 0 ? left.slice(0, pipe) : left;
    const hash = target.indexOf('#');
    const heading = hash >= 0 ? target.slice(hash + 1).trim() : '';
    // A trailing "#" with nothing after it stays part of the title.
    const title = (heading !== '' || target.trim().startsWith('#') ? target.slice(0, hash) : target).trim();

    return {
        title,
        heading,
        category,
        displayStart: hasAlias ? pipe + 1 : 0,
        displayEnd: hasAlias || pipe < 0 ? left.length : pipe,
    };
}

// Headings are matched ignoring case, inline Markdown markers and repeated whitespace.
export function headingKey(text) {
    return text
        .replace(/!?\[([^\]]*)\]\([^)]*\)/g, '$1')
        .replace(/[*_`~]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

export function headingSlug(text) {
    return headingKey(text).replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
}

// Finds ATX and setext headings outside fenced code, with the offsets of their lines.
export function extractHeadings(text) {
    const headings = [];
    let offset = 0;
    let fence = null;
    let previous = null;

    for (const rawLine of text.split('\n')) {
        const from = offset;
        offset += rawLine.length + 1;
        const line = rawLine.replace(/\r$/, '');
        const fenceMatch = /^ {0,3}(`{3,}|~{3,})/.exec(line);

        if (fence) {
            if (fenceMatch && fenceMatch[1][0] === fence[0] && fenceMatch[1].length >= fence.length) {
                fence = null;
            }
            continue;
        }
        if (fenceMatch) {
            fence = fenceMatch[1];
            previous = null;
            continue;
        }

        const atx = /^ {0,3}(#{1,6})[ \t]+(.*?)(?:[ \t]+#+)?[ \t]*$/.exec(line);
        if (atx) {
            headings.push({ text: atx[2].trim(), level: atx[1].length, from, to: from + line.length });
            previous = null;
            continue;
        }

        const setext = /^ {0,3}(=+|-+)[ \t]*$/.exec(line);
        if (setext && previous) {
            headings.push({ text: previous.text, level: setext[1][0] === '=' ? 1 : 2, from: previous.from, to: previous.to });
            previous = null;
            continue;
        }

        previous = line.trim() === '' || /^\s*(?:[-*+>]|\d+[.)])\s/.test(line)
            ? null
            : { text: line.trim(), from, to: from + line.length };
    }

    return headings.filter((heading) => heading.text !== '');
}

export function findHeading(headings, name) {
    const key = headingKey(name);

    return key === '' ? undefined : headings.find((heading) => headingKey(heading.text) === key);
}

function codeRanges(text) {
    const ranges = [];
    let fenceStart = null;
    let fence = null;
    let offset = 0;

    for (const line of text.split('\n')) {
        const match = /^ {0,3}(`{3,}|~{3,})/.exec(line);
        if (fence === null && match) {
            fence = match[1];
            fenceStart = offset;
        } else if (fence !== null && match && match[1][0] === fence[0] && match[1].length >= fence.length) {
            ranges.push([fenceStart, offset + line.length]);
            fence = null;
        }
        offset += line.length + 1;
    }
    if (fence !== null) {
        ranges.push([fenceStart, text.length]);
    }
    for (const match of text.matchAll(/`[^`\n]+`/g)) {
        ranges.push([match.index, match.index + match[0].length]);
    }

    return ranges;
}

// Internal links are a single-bracketed title, e.g. [Geography], that matches a heading in the same text.
// Anything else using single brackets (inline and reference links, images, footnotes, task boxes,
// wikilinks, code) is left alone, as is bracketed text with no matching heading.
export function findInternalLinks(text, headings = extractHeadings(text)) {
    if (headings.length === 0) {
        return [];
    }

    const code = codeRanges(text);
    const links = [];

    for (const match of text.matchAll(/(?<![[\]!\\])\[([^[\]\n]+)\](?![[\](:])/g)) {
        const label = match[1];
        const from = match.index;
        if (label.startsWith('^') || /^[ xX]$/.test(label) || code.some(([start, end]) => from >= start && from < end)) {
            continue;
        }
        const heading = findHeading(headings, label);
        if (heading) {
            links.push({ from, to: from + match[0].length, label, heading });
        }
    }

    return links;
}
