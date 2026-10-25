<x-layouts::auth title="Masuk">
    <div class="auth-card">
        <header class="auth-card-header">
            <span class="auth-kicker">SELAMAT DATANG</span>
            <h2>Masuk ke akun Anda</h2>
            <p>Akses membership dan latihan Anda dengan aman.</p>
        </header>

        <x-auth-session-status class="auth-session-status" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <label class="auth-field">
                <span>Email</span>
                <span class="auth-input-wrap">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>
                    <input name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email" placeholder="nama@email.com">
                </span>
                @error('email')<small class="auth-error">{{ $message }}</small>@enderror
            </label>

            <div class="auth-field">
                <span class="auth-label-row">
                    <label for="login-password">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" wire:navigate>Lupa password?</a>
                    @endif
                </span>
                <span class="auth-input-wrap">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                    <input id="login-password" class="auth-password-input" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password">
                    <button type="button" class="auth-password-toggle" data-password-toggle aria-controls="login-password" aria-label="Tampilkan password" aria-pressed="false">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path data-password-slash d="m3 3 18 18" hidden/></svg>
                    </button>
                </span>
                @error('password')<small class="auth-error">{{ $message }}</small>@enderror
            </div>

            <label class="auth-check">
                <input type="checkbox" name="remember" @checked(old('remember'))>
                <span>Ingat saya</span>
            </label>

            <button type="submit" class="auth-submit" data-test="login-button">
                <span>Masuk</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
            </button>
        </form>

        <div class="auth-help">
            <span>Belum memiliki akun?</span>
            <strong>Hubungi admin Awan Gym</strong>
        </div>
    </div>
</x-layouts::auth>
