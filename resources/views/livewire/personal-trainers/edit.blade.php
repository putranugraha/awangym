<?php

use App\Models\PersonalTrainer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public PersonalTrainer $trainer;
    public string $full_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $bio = '';
    public string $employment_status = 'active';
    public string $account_status = 'active';

    public function mount(PersonalTrainer $trainer): void
    {
        $this->trainer = $trainer->load('user');
        $this->fill([
            'full_name' => $trainer->user->full_name,
            'email' => $trainer->user->email,
            'phone' => $trainer->user->phone,
            'bio' => $trainer->bio ?? '',
            'employment_status' => $trainer->employment_status,
            'account_status' => $trainer->user->account_status,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->trainer->user_id, 'user_id')],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'bio' => ['nullable', 'string'],
            'employment_status' => ['required', 'in:active,inactive'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);

        DB::transaction(function () use ($data) {
            $userData = collect($data)->only(['full_name', 'email', 'phone', 'account_status'])->all();
            if ($data['password'] !== '') {
                $userData['password'] = $data['password'];
            }
            $this->trainer->user->update($userData);
            $this->trainer->update([
                'bio' => $data['bio'],
                'employment_status' => $data['employment_status'],
            ]);
        });

        session()->flash('success', 'Trainer berhasil diperbarui.');
        $this->redirectRoute('personal-trainers.index', navigate: true);
    }
};
?>

@php
    $identityComplete = filled($full_name) && filled($email) && filled($phone);
    $securityComplete = $password === '' || strlen($password) >= 8;
    $statusComplete = filled($account_status) && filled($employment_status);
    $completedSections = collect([$identityComplete, $securityComplete, $statusComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $trainer->trainer_code }}</span><h1>Edit Personal Trainer</h1><p>Perbarui identitas, akses, dan status trainer.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('personal-trainers.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card trainer-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas Trainer</h2><p>Informasi dasar dan kontak personal trainer.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Nama lengkap trainer" autocomplete="name"></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="trainer@email.com" autocomplete="email"></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel"></label>
            </div>
            <label><span>Bio</span><textarea class="form-input" wire:model="bio" rows="3" placeholder="Deskripsi singkat trainer (opsional)"></textarea></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Keamanan Akun</h2><p>Ubah password hanya jika diperlukan.</p></div></div>
            <label><span>Password baru</span><input class="form-input" type="password" wire:model.live.debounce.300ms="password" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password"></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Status Trainer</h2><p>Atur status pekerjaan dan akses login.</p></div></div>
            <div class="form-grid">
                <label><span>Status pekerjaan <em>*</em></span><select class="form-input" wire:model.live="employment_status"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                <label><span>Status akun <em>*</em></span><select class="form-input" wire:model.live="account_status"><option value="active">Aktif — dapat login</option><option value="inactive">Nonaktif — akses diblokir</option></select></label>
            </div>
        </section>
        <aside class="form-side-stack">
            <section class="form-card trainer-identity-card">
                <span class="member-identity-avatar">{{ $trainer->user->initials() }}</span>
                <div><strong>{{ $full_name ?: $trainer->user->full_name }}</strong><small>{{ $trainer->trainer_code }} · {{ $email }}</small></div>
                <span class="account-status account-status-{{ $account_status }}">{{ $account_status === 'active' ? 'Dapat login' : 'Diblokir' }}</span>
            </section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan trainer</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Identitas trainer</strong><small>Nama dan informasi kontak</small></span></li>
                    <li class="{{ $securityComplete ? 'is-complete' : '' }}"><i>{{ $securityComplete ? '✓' : '2' }}</i><span><strong>Keamanan akun</strong><small>Password tetap atau diperbarui</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '3' }}</i><span><strong>Status trainer</strong><small>Pekerjaan dan akses login</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('personal-trainers.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
