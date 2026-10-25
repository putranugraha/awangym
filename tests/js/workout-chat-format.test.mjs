import test from 'node:test';
import assert from 'node:assert/strict';
import { parseChatMessage } from '../../resources/js/workout-chat-format.js';

test('recommendation renders intro, readable bullet items and closing question', () => {
    const blocks = parseChatMessage('Bisa, kita mulai bertahap.\n\n- **Teknik:** fokus gerakan dasar.\n- **Waktu:** sesuaikan jadwalmu.\n\nKamu latihan di rumah? 💪');
    assert.deepEqual(blocks.map((block) => block.type), ['paragraph', 'list', 'paragraph']);
    assert.equal(blocks[1].items.length, 2);
    assert.deepEqual(blocks[1].items[0][0], { text: 'Teknik:', bold: true });
    assert.equal(blocks[2].spans[0].text, 'Kamu latihan di rumah? 💪');
});

test('supports ordered steps, compact headings and CRLF line endings', () => {
    const blocks = parseChatMessage('**Urutan latihan**\r\n1. Pemanasan\r\n2. **Gerakan utama**\r\n3. Selesai 💪');
    assert.equal(blocks[0].type, 'heading');
    assert.equal(blocks[1].ordered, true);
    assert.equal(blocks[1].start, 1);
    assert.equal(blocks[1].items.length, 3);
});

test('raw HTML and markdown links remain text rather than executable markup', () => {
    const payload = '<img src=x onerror=alert(1)> [tautan](javascript:alert(1))';
    const blocks = parseChatMessage(payload);
    assert.deepEqual(blocks, [{ type: 'paragraph', spans: [{ text: payload, bold: false }] }]);
});

test('plain short answers do not get unnecessary headings or lists', () => {
    const blocks = parseChatMessage('Aku bantu pilih latihannya. Kamu baru mulai? 💪');
    assert.equal(blocks.length, 1);
    assert.equal(blocks[0].type, 'paragraph');
});
