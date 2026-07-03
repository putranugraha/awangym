<?php

use App\Models\PersonalTrainer;
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
    public string $bio = '';
    public string $employment_status = '';
    public string $account_status = '';

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'bio' => ['nullable', 'string'],
            'employment_status' => ['required', 'in:active,inactive'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'account_status' => $data['account_status'],
                'email_verified_at' => now(),
            ]);
            $user->assignRole('personal_trainer');

            $trainer = PersonalTrainer::create([
                'user_id' => $user->user_id,
                'trainer_code' => 'TMP-'.Str::upper(Str::random(12)),
                'bio' => $data['bio'],
                'employment_status' => $data['employment_status'],
            ]);
            $trainer->update([
                'trainer_code' => 'AGT-'.str_pad((string) $trainer->trainer_id, 3, '0', STR_PAD_LEFT),
            ]);
        });

        session()->flash('success', 'Trainer berhasil ditambahkan.');
        $this->redirectRoute('personal-trainers.index', navigate: true);
    }
};
?>

@php
    $identityComplete = filled($full_name) && filled($email) && filled($phone);
    $securityComplete = strlen($password) >= 8;
    $statusComplete = filled($account_status) && filled($employment_status);
    $completedSections = collect([$identityComplete, $securityComplete, $statusComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">TRAINER BARU</span><h1>Tambah Personal Trainer</h1><p>Buat akun trainer untuk pendampingan member.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('personal-trainers.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card trainer-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas Trainer</h2><p>Informasi dasar dan kontak personal trainer.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Contoh: Sari Trainer" autocomplete="name"></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="trainer@email.com" autocomplete="email"></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel"></label>
            </div>
            <label><span>Bio</span><textarea class="form-input" wire:model="bio" rows="3" placeholder="Deskripsi singkat trainer (opsional)"></textarea></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Keamanan Akun</h2><p>Password awal untuk mengakses sistem.</p></div></div>
            <label><span>Password awal <em>*</em></span><input class="form-input" type="password" wire:model.live.debounce.300ms="password" placeholder="Minimal 8 karakter" autocomplete="new-password"></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Status Trainer</h2><p>Atur status pekerjaan dan akses login.</p></div></div>
            <div class="form-grid">
                <label><span>Status pekerjaan <em>*</em></span><select class="form-input" wire:model.live="employment_status"><option value="">Pilih status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                <label><span>Status akun <em>*</em></span><select class="form-input" wire:model.live="account_status"><option value="">Pilih status</option><option value="active">Aktif — dapat login</option><option value="inactive">Nonaktif — akses diblokir</option></select></label>
            </div>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan trainer</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Identitas trainer</strong><small>Nama dan informasi kontak</small></span></li>
                    <li class="{{ $securityComplete ? 'is-complete' : '' }}"><i>{{ $securityComplete ? '✓' : '2' }}</i><span><strong>Keamanan akun</strong><small>Password minimal 8 karakter</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '3' }}</i><span><strong>Status trainer</strong><small>Pekerjaan dan akses login</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Trainer</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('personal-trainers.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
