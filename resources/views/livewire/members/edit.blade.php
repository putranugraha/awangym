<?php

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public Member $member;

    public string $full_name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $gender = 'L';

    public string $birth_date = '';

    public string $address = '';

    public string $registered_at = '';

    public string $account_status = 'active';

    public function mount(Member $member): void
    {
        $this->member = $member->load('user');
        $this->fill([
            'full_name' => $member->user->full_name, 'email' => $member->user->email, 'phone' => $member->user->phone,
            'gender' => $member->gender, 'birth_date' => $member->birth_date->format('Y-m-d'), 'address' => $member->address,
            'registered_at' => $member->registered_at->format('Y-m-d'), 'account_status' => $member->user->account_status,
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'full_name' => ['required', 'max:100'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->member->user_id, 'user_id')],
            'phone' => ['required', 'max:20'], 'password' => ['nullable', 'min:8'], 'gender' => ['required', 'in:L,P'],
            'birth_date' => ['required', 'date', 'before:today'], 'address' => ['required'], 'registered_at' => ['required', 'date'],
            'account_status' => ['required', 'in:active,inactive'],
        ]);
        DB::transaction(function () use ($data) {
            $userData = collect($data)->only(['full_name', 'email', 'phone', 'account_status'])->all();
            if ($data['password'] !== '') {
                $userData['password'] = $data['password'];
            }
            $this->member->user->update($userData);
            $this->member->update(collect($data)->only(['gender', 'birth_date', 'address', 'registered_at'])->all());
        });
        session()->flash('success', 'Data member berhasil diperbarui.');
        $this->redirectRoute('members.index', navigate: true);
    }

};
?>

@php
    $accountComplete = filled($full_name) && filled($email) && filled($phone);
    $personalComplete = filled($birth_date) && filled($address);
    $registrationComplete = filled($registered_at) && filled($account_status);
    $completedSections = collect([$accountComplete, $personalComplete, $registrationComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $member->member_code }}</span><h1>Edit Member</h1><p>Perbarui informasi akun dan profil {{ $member->user->full_name }}.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
    </header>

    <form wire:submit="save" class="form-layout">
        <section class="form-card member-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Informasi Akun</h2><p>Identitas dan akses login member.</p></div></div>
            <label><span>Nama lengkap <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="full_name" placeholder="Nama lengkap member" autocomplete="name" required></label>
            <div class="form-grid">
                <label><span>Email <em>*</em></span><input class="form-input" type="email" wire:model.live.debounce.300ms="email" placeholder="member@email.com" autocomplete="email" required></label>
                <label><span>Nomor telepon <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="phone" placeholder="08xxxxxxxxxx" inputmode="tel" autocomplete="tel" required></label>
            </div>
            <label><span>Password baru</span><input class="form-input" type="password" wire:model="password" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password"></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Data Pribadi</h2><p>Informasi dasar profil member.</p></div></div>
            <div class="form-grid">
                <label><span>Jenis kelamin <em>*</em></span><select class="form-input" wire:model.live="gender"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
                <label><span>Tanggal lahir <em>*</em></span><input class="form-input" type="date" wire:model.live="birth_date" required></label>
            </div>
            <label><span>Alamat <em>*</em></span><textarea class="form-input" wire:model.live.debounce.300ms="address" rows="3" placeholder="Alamat lengkap member" required></textarea></label>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Status Pendaftaran</h2><p>Atur tanggal terdaftar dan akses akun.</p></div></div>
            <div class="form-grid">
                <label><span>Tanggal daftar <em>*</em></span><input class="form-input" type="date" wire:model.live="registered_at" required></label>
                <label><span>Status akun <em>*</em></span><select class="form-input" wire:model.live="account_status"><option value="active">Aktif — dapat login</option><option value="inactive">Nonaktif — akses diblokir</option></select></label>
            </div>
        </section>

        <aside class="form-side-stack">
            <section class="form-card member-identity-card">
                <span class="member-identity-avatar">{{ $member->user->initials() }}</span>
                <div><strong>{{ $full_name ?: $member->user->full_name }}</strong><small>{{ $member->member_code }} · Terdaftar {{ $member->registered_at->format('d M Y') }}</small></div>
                <span class="account-status account-status-{{ $account_status }}">{{ $account_status === 'active' ? 'Dapat login' : 'Diblokir' }}</span>
            </section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan data</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $accountComplete ? 'is-complete' : '' }}"><i>{{ $accountComplete ? '✓' : '1' }}</i><span><strong>Informasi akun</strong><small>Nama, email, dan telepon</small></span></li>
                    <li class="{{ $personalComplete ? 'is-complete' : '' }}"><i>{{ $personalComplete ? '✓' : '2' }}</i><span><strong>Data pribadi</strong><small>Tanggal lahir dan alamat</small></span></li>
                    <li class="{{ $registrationComplete ? 'is-complete' : '' }}"><i>{{ $registrationComplete ? '✓' : '3' }}</i><span><strong>Status pendaftaran</strong><small>Tanggal daftar dan akses akun</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('members.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>

