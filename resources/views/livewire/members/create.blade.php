<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $gender = '';

    public string $birth_date = '';

    public string $address = '';

    public string $registered_at = '';

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'max:20'], 'password' => ['required', 'min:8'], 'gender' => ['required', 'in:L,P'],
            'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required'], 'registered_at' => ['required', 'date'],
        ]);
        DB::transaction(function () use ($data) {
            $user = User::create($data + ['account_status' => 'active', 'email_verified_at' => now()]);
            $user->assignRole('member');
            $member = Member::create($data + [
                'user_id' => $user->user_id,
                'member_code' => 'TMP-'.Str::upper(Str::random(12)),
            ]);
            $member->update([
                'member_code' => 'AGM-'.str_pad((string) $member->member_id, 3, '0', STR_PAD_LEFT),
            ]);
        });
        session()->flash('success', 'Member berhasil ditambahkan.');
        $this->redirectRoute('members.index', navigate: true);
    }

};
?>

@php
    $accountComplete = filled($full_name) && filled($email) && filled($phone) && strlen($password) >= 8;
    $personalComplete = filled($birth_date) && filled($address);
    $registrationComplete = filled($registered_at);
    $completedSections = collect([$accountComplete, $personalComplete, $registrationComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">MEMBER BARU</span><h1>Tambah Member</h1><p>Lengkapi data utama untuk membuat akun member Awan Gym.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
    </header>

    <form wire:submit="save" class="form-layout">
        <section class="form-card member-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Informasi Akun</h2><p>Data yang digunakan member untuk masuk ke aplikasi.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Contoh: Budi Santoso" autocomplete="name" required></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="budi@email.com" autocomplete="email" required></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel" required></label>
            </div>
            <label><span>Password awal <em>*</em></span><input class="form-input" type="password" wire:model.live.debounce.300ms="password" placeholder="Minimal 8 karakter" minlength="8" autocomplete="new-password" required></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Data Pribadi</h2><p>Informasi dasar untuk melengkapi profil member.</p></div></div>
            <div class="form-grid">
                <label><span>Jenis kelamin <em>*</em></span><select class="form-input" wire:model.live="gender"><option value="">Pilih jenis kelamin</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                <label><span>Tanggal lahir <em>*</em></span><input class="form-input" type="date" wire:model.live="birth_date" required></label>
            </div>
            <label><span>Alamat <em>*</em></span><textarea class="form-input" wire:model.live.debounce.300ms="address" rows="3" placeholder="Masukkan alamat lengkap member" required></textarea></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Informasi Pendaftaran</h2><p>Tanggal member mulai terdaftar di sistem.</p></div></div>
            <label><span>Tanggal daftar <em>*</em></span><input class="form-input" type="date" wire:model.live="registered_at" required></label>
        </section>

        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan data</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $accountComplete ? 'is-complete' : '' }}"><i>{{ $accountComplete ? '✓' : '1' }}</i><span><strong>Informasi akun</strong><small>Nama, email, telepon, dan password</small></span></li>
                    <li class="{{ $personalComplete ? 'is-complete' : '' }}"><i>{{ $personalComplete ? '✓' : '2' }}</i><span><strong>Data pribadi</strong><small>Tanggal lahir dan alamat</small></span></li>
                    <li class="{{ $registrationComplete ? 'is-complete' : '' }}"><i>{{ $registrationComplete ? '✓' : '3' }}</i><span><strong>Pendaftaran</strong><small>Tanggal mulai terdaftar</small></span></li>
                </ul>
            </section>
            <div class="member-form-note"><strong>Setelah disimpan</strong><p>Akun langsung aktif, tetapi membership baru aktif setelah transaksi paket berstatus paid.</p></div>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Member</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>

