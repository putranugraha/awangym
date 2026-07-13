<?php

use App\Models\MembershipPackage;
use Livewire\Component;

new class extends Component
{
    public string $package_name = '';

    public string $description = '';

    public string $package_status = '';

    public int|string $duration_months = '';

    public int|string $price = '';

    public bool $has_trainer = false;

    public int|string $trainer_session_limit = '';

    public function updatedHasTrainer(bool $value): void
    {
        if ($value && $this->trainer_session_limit === '') {
            $this->trainer_session_limit = max((int) $this->duration_months * 12, 12);
        }
    }

    public function updatedDurationMonths(): void
    {
        $maximum = max((int) $this->duration_months * 12, 1);
        if ($this->has_trainer && ($this->trainer_session_limit === '' || (int) $this->trainer_session_limit > $maximum)) {
            $this->trainer_session_limit = $maximum;
        }
    }

    public function save(): void
    {
        $maxTrainerSessions = max((int) $this->duration_months * 12, 1);
        $data = $this->validate([
            'package_name' => ['required', 'max:100'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'price' => ['required', 'numeric', 'min:0'],
            'has_trainer' => ['boolean'],
            'trainer_session_limit' => ['nullable', 'integer', 'min:1', 'max:'.$maxTrainerSessions, 'required_if:has_trainer,true'],
            'description' => ['nullable'],
            'package_status' => ['required', 'in:active,inactive']
        ]);

        if ($data['has_trainer']) {
            $data['price'] = (float)$data['price'] + ((int)$data['duration_months'] * 1800000);
        } else {
            $data['trainer_session_limit'] = null;
        }

        MembershipPackage::create($data);
        session()->flash('success', 'Paket berhasil disimpan.');
        $this->redirectRoute('packages.index', navigate: true);
    }

};
?>

@php
    $identityComplete = filled($package_name);
    $pricingComplete = filled($duration_months) && $price !== '';
    $trainerComplete = true;
    $statusComplete = filled($package_status);
    $completedSections = collect([$identityComplete, $pricingComplete, $trainerComplete, $statusComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">PAKET BARU</span><h1>Tambah Paket</h1><p>Buat pilihan membership baru untuk member Awan Gym.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('packages.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card package-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Informasi Paket</h2><p>Nama dan deskripsi paket membership.</p></div></div>
            <label><span>Nama paket <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="package_name" placeholder="Contoh: Membership 1 Bulan"></label>
            <label><span>Deskripsi</span><textarea class="form-input" wire:model="description" rows="3" placeholder="Deskripsi singkat paket (opsional)"></textarea></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Durasi dan Harga</h2><p>Tentukan masa berlaku dan harga paket.</p></div></div>
            <div class="form-grid">
                <label><span>Durasi (bulan) <em>*</em></span><input class="form-input" type="number" min="1" max="60" wire:model.live.debounce.300ms="duration_months" placeholder="Contoh: 1"></label>
                <label><span>Harga Base Membership <em>*</em></span><input class="form-input" type="number" min="0" wire:model.live.debounce.300ms="price" placeholder="Contoh: 150000"></label>
            </div>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Personal Trainer</h2><p>Tentukan apakah paket ini termasuk layanan pendampingan trainer.</p></div></div>
            <div style="margin-top: 1rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" wire:model.live="has_trainer" style="width: 1.25rem; height: 1.25rem; accent-color: var(--color-primary);">
                    <span><strong>Termasuk Personal Trainer</strong> (Member wajib didampingi trainer saat berlangganan paket ini)</span>
                </label>
            </div>
            @if($has_trainer)
                <label class="mt-3"><span>Jumlah pertemuan PT <em>*</em></span><input class="form-input" type="number" min="1" max="{{ max((int) $duration_months * 12, 1) }}" wire:model.live="trainer_session_limit" placeholder="Contoh: 12"><small class="trainer-session-help">Maksimal 12 pertemuan per bulan ({{ max((int) $duration_months * 12, 0) }} sesi untuk durasi ini).</small></label>
                <div class="mt-3 p-3 bg-zinc-50 border border-zinc-200 rounded-lg dark:bg-zinc-800/50 dark:border-zinc-700">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Estimasi Sesi & Biaya Tambahan Trainer:</p>
                    @if(filled($duration_months) && (int)$duration_months > 0)
                        <ul class="mt-1 list-disc list-inside text-sm text-zinc-600 dark:text-zinc-400">
                            <li>Total Pertemuan: <strong>{{ (int) ($trainer_session_limit ?: 0) }}x pertemuan</strong></li>
                            <li>Total Biaya Trainer: <strong>Rp {{ number_format((int) $duration_months * 1800000, 0, ',', '.') }}</strong> (Rp 1.800.000/bulan)</li>
                            <li style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--color-slate-200); list-style: none; color: var(--color-primary); font-weight: 600;">
                                Total Harga Paket: Rp {{ number_format((float)($price ?: 0) + ((int)$duration_months * 1800000), 0, ',', '.') }}
                            </li>
                        </ul>
                    @else
                        <p class="mt-1 text-sm text-zinc-500 italic">Silakan isi durasi paket terlebih dahulu untuk melihat estimasi sesi dan harga.</p>
                    @endif
                </div>
            @endif

            <div class="form-section-title member-section-gap"><span>04</span><div><h2>Ketersediaan</h2><p>Atur apakah paket dapat dipilih saat transaksi.</p></div></div>
            <label><span>Status <em>*</em></span><select class="form-input" wire:model.live="package_status"><option value="">Pilih status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan paket</span><strong>{{ $completedSections }}/4 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 4) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Informasi paket</strong><small>Nama paket membership</small></span></li>
                    <li class="{{ $pricingComplete ? 'is-complete' : '' }}"><i>{{ $pricingComplete ? '✓' : '2' }}</i><span><strong>Durasi dan harga</strong><small>Masa berlaku dan nominal</small></span></li>
                    <li class="{{ $trainerComplete ? 'is-complete' : '' }}"><i>{{ $trainerComplete ? '✓' : '3' }}</i><span><strong>Personal Trainer</strong><small>Layanan pendampingan PT</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '4' }}</i><span><strong>Ketersediaan</strong><small>Status paket</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Paket</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('packages.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>
