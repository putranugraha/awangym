<?php

use App\Models\User;
use Livewire\Component;

new class extends Component
{
    public string $full_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $account_status = '';

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::create($data + ['email_verified_at' => now()]);
        $user->assignRole('admin');

        session()->flash('success', 'User administrator berhasil ditambahkan.');
        $this->redirectRoute('users.index', navigate: true);
    }
};
?>

@php
    $identityComplete = filled($full_name) && filled($email) && filled($phone);
    $securityComplete = strlen($password) >= 8;
    $statusComplete = filled($account_status);
    $completedSections = collect([$identityComplete, $securityComplete, $statusComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">USER BARU</span><h1>Tambah User</h1><p>Buat akun administrator untuk operasional Awan Gym.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('users.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card user-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas User</h2><p>Informasi dasar akun administrator.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Contoh: Admin Operasional" autocomplete="name"></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="admin@awangym.com" autocomplete="email"></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx" inputmode="tel"></label>
            </div>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Keamanan Akun</h2><p>Password awal untuk login.</p></div></div>
            <label><span>Password <em>*</em></span><input class="form-input" type="password" wire:model.live.debounce.300ms="password" placeholder="Minimal 8 karakter" autocomplete="new-password"></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Akses Akun</h2><p>Role otomatis ditetapkan sebagai administrator.</p></div></div>
            <label><span>Status akun <em>*</em></span><select class="form-input" wire:model.live="account_status"><option value="">Pilih status</option><option value="active">Aktif — dapat login</option><option value="inactive">Nonaktif — akses diblokir</option></select></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card user-role-card"><span class="member-identity-avatar">A</span><div><strong>Administrator</strong><small>Mengelola operasional Awan Gym</small></div></section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan user</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Identitas user</strong><small>Nama dan kontak</small></span></li>
                    <li class="{{ $securityComplete ? 'is-complete' : '' }}"><i>{{ $securityComplete ? '✓' : '2' }}</i><span><strong>Keamanan akun</strong><small>Password minimal 8 karakter</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '3' }}</i><span><strong>Akses akun</strong><small>Status login administrator</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan User</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('users.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
