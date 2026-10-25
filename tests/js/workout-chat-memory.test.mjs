import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

// Exercise the actual custom element without requiring a browser or DOM package.
globalThis.HTMLElement = class {};
let Chat;
globalThis.customElements = { get: () => undefined, define: (_name, type) => { Chat = type; } };
const source = (await readFile(new URL('../../resources/js/workout-chat.js', import.meta.url), 'utf8'))
    .replace("import { renderChatMessage } from './workout-chat-format';", 'const renderChatMessage = () => {};');
await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function widget() {
    const chat = new Chat();
    chat.dataset = { historyEndpoint: '/workout-chat/history' };
    chat.isConnected = true;
    chat.status = { textContent: '' };
    chat.rendered = [];
    chat.messages = { replaceChildren: () => { chat.rendered = []; } };
    chat.appendMessage = (role, text) => chat.rendered.push({ role, text });
    return chat;
}

test('restores server history once and shares concurrent history requests', async () => {
    const chat = widget();
    let calls = 0;
    globalThis.fetch = async (url, options) => {
        calls++;
        assert.equal(url, '/workout-chat/history');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.cache, 'no-store');
        return { ok: true, json: async () => ({ turns: [{ user: 'Aku pemula', assistant: 'Mau latihan di mana?' }] }) };
    };
    await Promise.all([chat.loadHistory(), chat.loadHistory()]);
    await chat.loadHistory();
    assert.equal(calls, 1);
    assert.deepEqual(chat.rendered, [
        { role: 'user', text: 'Aku pemula' }, { role: 'assistant', text: 'Mau latihan di mana?' },
    ]);
});

test('failed history request can be retried without overwriting existing content', async () => {
    const chat = widget();
    chat.rendered.push({ role: 'assistant', text: 'Hai' });
    globalThis.fetch = async () => ({ ok: false });
    await assert.rejects(chat.loadHistory());
    assert.equal(chat.historyLoaded, undefined);
    assert.equal(chat.rendered.length, 1);
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ turns: [] }) });
    await chat.loadHistory();
    assert.equal(chat.historyLoaded, true);
    assert.equal(chat.rendered[0].text, 'Hai');
});

test('a response for a disconnected widget does not render private history', async () => {
    const chat = widget();
    globalThis.fetch = async () => {
        chat.isConnected = false;
        return { ok: true, json: async () => ({ turns: [{ user: 'Private', assistant: 'Private' }] }) };
    };
    await assert.rejects(chat.loadHistory());
    assert.deepEqual(chat.rendered, []);
});
