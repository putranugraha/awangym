<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public User $user;
    public string $full_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $account_status = '';

    public function mount(User $user): void
    {
        $this->user = $user->load(['roles', 'member', 'personalTrainer']);
        $this->fill($user->only(['full_name', 'email', 'phone', 'account_status']));
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->user->user_id, 'user_id')],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);

        if ($this->user->is(auth()->user()) && $data['account_status'] === 'inactive') {
            throw ValidationException::withMessages(['account_status' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.']);
        }

        if ($data['password'] === '') {
            unset($data['password']);
        }
        $this->user->update($data);

        session()->flash('success', 'Data user berhasil diperbarui.');
        $this->redirectRoute('users.index', navigate: true);
    }
};
?>

@php
    $identityComplete = filled($full_name) && filled($email) && filled($phone);
    $securityComplete = $password === '' || strlen($password) >= 8;
    $statusComplete = filled($account_status);
    $completedSections = collect([$identityComplete, $securityComplete, $statusComplete])->filter()->count();
    $roleName = $user->roles->first()?->name;
    $roleLabel = match($roleName) {'personal_trainer' => 'Personal Trainer', 'member' => 'Member', default => 'Administrator'};
    $profileCode = $user->member?->member_code ?? $user->personalTrainer?->trainer_code ?? 'Akun operasional';
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">USER #{{ $user->user_id }}</span><h1>Edit User</h1><p>Perbarui identitas dan akses akun {{ $user->full_name }}.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('users.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card user-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Identitas User</h2><p>Informasi dasar dan kontak akun.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Nama lengkap user"></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="user@email.com"></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx"></label>
            </div>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Keamanan Akun</h2><p>Ubah password hanya jika diperlukan.</p></div></div>
            <label><span>Password baru</span><input class="form-input" type="password" wire:model.live.debounce.300ms="password" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password"></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Akses Akun</h2><p>Status menentukan apakah user dapat login.</p></div></div>
            <label><span>Status akun <em>*</em></span><select class="form-input" wire:model.live="account_status"><option value="active">Aktif — dapat login</option><option value="inactive">Nonaktif — akses diblokir</option></select></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card user-identity-card">
                <span class="member-identity-avatar">{{ $user->initials() }}</span>
                <div><strong>{{ $full_name ?: $user->full_name }}</strong><small>{{ $roleLabel }} · {{ $profileCode }}</small></div>
                <span class="account-status account-status-{{ $account_status }}">{{ $account_status === 'active' ? 'Dapat login' : 'Diblokir' }}</span>
            </section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan user</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Identitas user</strong><small>Nama dan kontak</small></span></li>
                    <li class="{{ $securityComplete ? 'is-complete' : '' }}"><i>{{ $securityComplete ? '✓' : '2' }}</i><span><strong>Keamanan akun</strong><small>Password tetap atau diperbarui</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '3' }}</i><span><strong>Akses akun</strong><small>Status login user</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('users.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
