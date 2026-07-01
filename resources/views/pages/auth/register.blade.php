<x-layouts::auth title="Daftar">
    <div class="auth-card">
        <header class="auth-card-header">
            <span class="auth-kicker">MULAI SEKARANG</span>
            <h2>Buat akun Awan Gym</h2>
            <p>Lengkapi data untuk memulai perjalanan latihan Anda.</p>
        </header>

        @if (Route::has('register.store'))
            <form method="POST" action="{{ route('register.store') }}" class="auth-form">
                @csrf
                <label class="auth-field"><span>Nama lengkap</span><span class="auth-input-wrap"><input name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Nama lengkap"></span>@error('name')<small class="auth-error">{{ $message }}</small>@enderror</label>
                <label class="auth-field"><span>Email</span><span class="auth-input-wrap"><input name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="nama@email.com"></span>@error('email')<small class="auth-error">{{ $message }}</small>@enderror</label>
                <label class="auth-field"><span>Password</span><span class="auth-input-wrap"><input name="password" type="password" required autocomplete="new-password" placeholder="Minimal 8 karakter"></span>@error('password')<small class="auth-error">{{ $message }}</small>@enderror</label>
                <label class="auth-field"><span>Konfirmasi password</span><span class="auth-input-wrap"><input name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Ulangi password"></span></label>
                <button type="submit" class="auth-submit" data-test="register-user-button"><span>Buat Akun</span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-5-5 5 5-5 5"/></svg></button>
            </form>
        @else
            <div class="auth-closed-registration">
                <span>Registrasi dikelola oleh admin</span>
                <p>Silakan datang atau hubungi Awan Gym untuk membuat akun membership.</p>
            </div>
        @endif

        <div class="auth-help"><span>Sudah memiliki akun?</span><a href="{{ route('login') }}" wire:navigate>Masuk sekarang</a></div>
    </div>
</x-layouts::auth>
