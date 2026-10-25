import { renderChatMessage } from './workout-chat-format';

class WorkoutChat extends HTMLElement {
    connectedCallback() {
        if (this.initialized) return;
        this.initialized = true;
        this.events = new AbortController();
        const options = { signal: this.events.signal };
        this.panel = this.querySelector('.workout-chat-panel');
        this.toggle = this.querySelector('.workout-chat-toggle');
        this.input = this.querySelector('textarea');
        this.form = this.querySelector('form');
        this.messages = this.querySelector('.workout-chat-messages');
        this.status = this.querySelector('.workout-chat-status');
        this.toggle.addEventListener('click', () => this.setOpen(this.panel.hidden), options);
        this.querySelector('[data-chat-close]').addEventListener('click', () => this.setOpen(false), options);
        this.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.panel.hidden) this.setOpen(false);
        }, options);
        this.querySelectorAll('[data-chat-question]').forEach((button) => {
            button.addEventListener('click', () => {
                if (this.busy) return;
                this.input.value = button.dataset.chatQuestion;
                this.form.requestSubmit();
            }, options);
        });
        this.input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                if (!this.busy) this.form.requestSubmit();
            }
        }, options);
        this.form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.send();
        }, options);
    }

    disconnectedCallback() {
        this.events?.abort();
        this.request?.abort();
        this.historyRequest?.abort();
        this.initialized = false;
    }

    setOpen(open) {
        this.panel.hidden = !open;
        this.toggle.setAttribute('aria-expanded', String(open));
        this.toggle.setAttribute('aria-label', open ? 'Tutup asisten program latihan' : 'Buka asisten program latihan');
        if (open) {
            this.input.focus();
            this.loadHistory().catch(() => {
                if (this.isConnected && !this.busy) this.status.textContent = 'Riwayat belum berhasil dimuat. Tutup dan buka chat untuk mencoba lagi.';
            });
        }
        else this.toggle.focus();
    }

    async loadHistory() {
        if (this.historyLoaded) return;
        if (this.historyLoading) return this.historyLoading;
        this.historyRequest = new AbortController();
        const timeout = setTimeout(() => this.historyRequest.abort(), 15000);
        this.historyLoading = (async () => {
            const response = await fetch(this.dataset.historyEndpoint, {
                credentials: 'same-origin', cache: 'no-store', signal: this.historyRequest.signal,
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Riwayat belum berhasil dimuat. Coba lagi sebentar.');
            const data = await response.json();
            if (!this.isConnected) throw new Error('Percakapan sudah ditutup.');
            if (data.turns.length) {
                this.messages.replaceChildren();
                data.turns.forEach((turn) => {
                    this.appendMessage('user', turn.user);
                    this.appendMessage('assistant', turn.assistant);
                });
            }
            this.historyLoaded = true;
            if (!this.busy) this.status.textContent = '';
        })();
        try {
            await this.historyLoading;
        } finally {
            clearTimeout(timeout);
            this.historyLoading = null;
        }
    }

    appendMessage(role, text) {
        const article = document.createElement('article');
        article.className = `workout-chat-message workout-chat-${role}`;
        if (role === 'assistant') {
            renderChatMessage(article, text);
        } else {
            const paragraph = document.createElement('p');
            paragraph.textContent = text;
            article.append(paragraph);
        }
        this.messages.append(article);
        // Bound client memory for long conversations.
        const messages = this.messages.querySelectorAll('article');
        if (messages.length > 24) messages[0].remove();
        this.messages.scrollTop = this.messages.scrollHeight;
    }

    async send() {
        const message = this.input.value.trim();
        if (!message || this.busy) return;
        this.busy = true;
        this.querySelectorAll('button[type="submit"], [data-chat-question]').forEach((button) => button.disabled = true);
        this.form.setAttribute('aria-busy', 'true');
        this.status.textContent = 'thinking...';
        this.request = new AbortController();
        const timeout = setTimeout(() => this.request.abort(), 60000);
        try {
            await this.loadHistory();
            this.appendMessage('user', message);
            this.input.value = '';
            const response = await fetch(this.dataset.endpoint, {
                method: 'POST', credentials: 'same-origin', signal: this.request.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.dataset.csrf },
                body: JSON.stringify({ message }),
            });
            if (!response.ok) {
                const errors = {
                    401: 'Sesi Anda berakhir. Masuk kembali, lalu coba lagi.',
                    419: 'Sesi Anda berakhir. Muat ulang halaman, lalu coba lagi.',
                    422: 'Tulis pertanyaan sepanjang 1–1.000 karakter.',
                    429: 'Batas pertanyaan tercapai. Tunggu satu menit, lalu coba lagi.',
                };
                throw new Error(errors[response.status] || 'Asisten belum dapat merespons. Coba kirim lagi sebentar.');
            }
            const data = await response.json();
            if (!this.isConnected) return;
            this.appendMessage('assistant', data.message);
            this.status.textContent = '';
        } catch (error) {
            if (!this.isConnected) return;
            this.status.textContent = error.name === 'AbortError' ? 'Respons terlalu lama. Coba kirim lagi.' : (error instanceof TypeError ? 'Koneksi terputus. Periksa jaringan dan coba lagi.' : error.message);
            this.input.value = message;
        } finally {
            clearTimeout(timeout);
            this.busy = false;
            this.form.removeAttribute('aria-busy');
            this.querySelectorAll('button[type="submit"], [data-chat-question]').forEach((button) => button.disabled = false);
        }
    }
}

if (!customElements.get('workout-chat')) customElements.define('workout-chat', WorkoutChat);
