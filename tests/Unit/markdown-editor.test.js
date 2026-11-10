import assert from 'node:assert/strict';
import test from 'node:test';
import { formatSelection, renderMarkdown } from '../../resources/js/markdown-formatting.js';

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
    assert.match(html, /<h1>Title<\/h1>/);
    assert.match(html, /<strong>bold<\/strong>/);
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>|href="javascript:/);
});
