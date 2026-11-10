import assert from 'node:assert/strict';
import test from 'node:test';
import { formatSelection, renderMarkdown } from '../../resources/js/markdown-formatting.js';
import { extractHeadings, findHeading, findInternalLinks, parseWikilink } from '../../resources/js/markdown-links.js';

test('formatting wraps selected text and retains its selection', () => {
    assert.deepEqual(formatSelection('a magic word', 2, 7, 'bold'), {
        from: 2, to: 7, insert: '**magic**', anchor: 4, head: 9,
    });
    assert.deepEqual(formatSelection('', 0, 0, 'wikilink'), {
        from: 0, to: 0, insert: '[[]]', anchor: 2, head: 2,
    });
});

test('line formatting applies to each selected line without including the next line', () => {
    assert.deepEqual(formatSelection('first\nsecond\nthird', 0, 13, 'bullet'), {
        from: 0, to: 12, insert: '- first\n- second', anchor: 0, head: 16,
    });
    assert.deepEqual(formatSelection('\nsecond', 0, 0, 'heading'), {
        from: 0, to: 0, insert: '## ', anchor: 0, head: 3,
    });
});

test('preview renders Markdown without executing HTML or unsafe links', () => {
    const html = renderMarkdown('# Title\n\n**bold** <script>alert(1)</script> [bad](javascript:alert(1))');
    assert.match(html, /<h1[^>]*>Title<\/h1>/);
    assert.match(html, /<strong>bold<\/strong>/);
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>|href="javascript:/);
});

test('wikilinks can target a heading, alongside aliases and categories', () => {
    assert.deepEqual(parseWikilink('Blah#Foo'), { title: 'Blah', heading: 'Foo', category: '', displayStart: 0, displayEnd: 8 });
    assert.deepEqual(parseWikilink('Blah#Foo|Shown@Books'), { title: 'Blah', heading: 'Foo', category: 'Books', displayStart: 9, displayEnd: 14 });
    assert.deepEqual(parseWikilink('Blah'), { title: 'Blah', heading: '', category: '', displayStart: 0, displayEnd: 4 });
    assert.equal(parseWikilink('Blah|Alias').heading, '');
    assert.equal(parseWikilink('C#').title, 'C#');
    assert.deepEqual(
        { title: parseWikilink('#Foo').title, heading: parseWikilink('#Foo').heading },
        { title: '', heading: 'Foo' },
    );
});

test('headings are found outside code and matched loosely', () => {
    const text = '# One\n\nSetext\n======\n\n```\n# not a heading\n```\n\n## **Two** `x` ##\n';
    const headings = extractHeadings(text);
    assert.deepEqual(headings.map((heading) => [heading.text, heading.level]), [['One', 1], ['Setext', 1], ['**Two** `x`', 2]]);
    assert.equal(findHeading(headings, 'two x')?.level, 2);
    assert.equal(findHeading(headings, 'missing'), undefined);
    assert.equal(text.slice(headings[0].from, headings[0].to), '# One');
});

test('single-bracket links resolve only to existing headings and leave other link syntax alone', () => {
    const text = [
        '# Geography',
        '',
        'See [geography] and [Missing].',
        'Not these: [Geography](https://x.test), [Geography][ref], ![Geography], [[Geography]], `[Geography]`, [^Geography].',
        '[Geography]: https://x.test',
        '```',
        '[Geography]',
        '```',
    ].join('\n');
    const links = findInternalLinks(text);
    assert.equal(links.length, 1);
    assert.equal(text.slice(links[0].from, links[0].to), '[geography]');
    assert.equal(links[0].heading.text, 'Geography');
});

test('preview renders internal links to heading anchors without touching other links', () => {
    const html = renderMarkdown('# Old Lands\n\nGo to [old lands], [Nowhere] or [site](https://x.test).');
    assert.match(html, /<h1 id="old-lands">Old Lands<\/h1>/);
    assert.match(html, /<a href="#old-lands">old lands<\/a>/);
    assert.match(html, /\[Nowhere\]/);
    assert.match(html, /<a href="https:\/\/x\.test">site<\/a>/);
});

