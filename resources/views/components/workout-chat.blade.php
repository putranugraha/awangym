<workout-chat data-endpoint="{{ route('workout-chat.store') }}" data-history-endpoint="{{ route('workout-chat.history') }}" data-csrf="{{ csrf_token() }}">
    <section id="workout-chat-panel" class="workout-chat-panel" role="dialog" aria-labelledby="workout-chat-title" hidden>
        <header class="workout-chat-header">
            <span class="workout-chat-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 10h8M8 14h5"/><path d="M20 11.5a8 8 0 0 1-8 8H5l-3 2v-10a9 9 0 0 1 18 0Z"/></svg></span>
            <div><h2 id="workout-chat-title">Asisten Program</h2><p>Temukan arah latihan Anda.</p></div>
            <button type="button" class="workout-chat-close" data-chat-close aria-label="Tutup percakapan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </header>
        <div class="workout-chat-messages" role="log" aria-label="Percakapan program latihan" aria-live="polite" aria-relevant="additions" tabindex="0">
            <article class="workout-chat-message workout-chat-assistant">
                <p>Hai! Mau mulai latihan, cari jadwal, atau pahami gerakan? Ceritakan tujuanmu, kita bahas langkah awalnya. 💪</p>
            </article>
            <div class="workout-chat-suggestions" aria-label="Contoh pertanyaan">
                <button type="button" data-chat-question="Program apa yang cocok untuk pemula?">Mulai dari program pemula</button>
                <button type="button" data-chat-question="Bagaimana jadwal program strength minggu 1?">Lihat jadwal strength</button>
                <button type="button" data-chat-question="Berapa set dan repetisi bench press pada minggu 1 sampai 4?">Set &amp; repetisi bench press</button>
            </div>
        </div>
        <div class="workout-chat-status" role="status" aria-live="polite"></div>
        <form class="workout-chat-form">
            <label class="sr-only" for="workout-chat-input">Pertanyaan tentang program latihan</label>
            <textarea id="workout-chat-input" rows="2" maxlength="1000" placeholder="Tanyakan program atau gerakan…" required></textarea>
            <button type="submit" aria-label="Kirim pertanyaan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m5 12 7-7 7 7M12 5v14"/></svg></button>
        </form>
        <p class="workout-chat-footnote">Khusus konsultasi program latihan. Penyesuaian pribadi? Diskusikan dengan trainer.</p>
    </section>
    <button type="button" class="workout-chat-toggle" aria-controls="workout-chat-panel" aria-expanded="false" aria-label="Buka asisten program latihan">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 10h8M8 14h5"/><path d="M20 11.5a8 8 0 0 1-8 8H5l-3 2v-10a9 9 0 0 1 18 0Z"/></svg>
        <span>Tanya Program</span>
    </button>
</workout-chat>
