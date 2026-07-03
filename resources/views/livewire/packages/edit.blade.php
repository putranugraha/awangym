<?php

use App\Models\MembershipPackage;
use Livewire\Component;

new class extends Component
{
    public MembershipPackage $package;

    public string $package_name = '';

    public string $description = '';

    public string $package_status = 'active';

    public int|string $duration_months = '';

    public int|string $price = '';

    public function mount(MembershipPackage $package): void
    {
        $this->package = $package;
        $this->fill($package->only(['package_name', 'duration_months', 'price', 'package_status']));
        $this->description = $package->description ?? '';
    }

    public function save(): void
    {
        $data = $this->validate(['package_name' => ['required', 'max:100'], 'duration_months' => ['required', 'integer', 'min:1', 'max:60'], 'price' => ['required', 'numeric', 'min:0'], 'description' => ['nullable'], 'package_status' => ['required', 'in:active,inactive']]);
        $this->package->update($data);
        session()->flash('success', 'Paket berhasil diperbarui.');
        $this->redirectRoute('packages.index', navigate: true);
    }

};
?>

@php
    $identityComplete = filled($package_name);
    $pricingComplete = filled($duration_months) && $price !== '';
    $statusComplete = filled($package_status);
    $completedSections = collect([$identityComplete, $pricingComplete, $statusComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">EDIT PAKET</span><h1>{{ $package->package_name }}</h1><p>Perbarui informasi dan ketersediaan paket membership.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('packages.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card package-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Informasi Paket</h2><p>Nama dan deskripsi paket membership.</p></div></div>
            <label><span>Nama paket <em>*</em></span><input class="form-input" wire:model.live.debounce.300ms="package_name" placeholder="Nama paket membership"></label>
            <label><span>Deskripsi</span><textarea class="form-input" wire:model="description" rows="3" placeholder="Deskripsi singkat paket (opsional)"></textarea></label>

            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Durasi dan Harga</h2><p>Perbarui masa berlaku dan harga paket.</p></div></div>
            <div class="form-grid">
                <label><span>Durasi (bulan) <em>*</em></span><input class="form-input" type="number" min="1" max="60" wire:model.live="duration_months" placeholder="Contoh: 1"></label>
                <label><span>Harga <em>*</em></span><input class="form-input" type="number" min="0" wire:model.live="price" placeholder="Contoh: 150000"></label>
            </div>

            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Ketersediaan</h2><p>Atur apakah paket dapat dipilih saat transaksi.</p></div></div>
            <label><span>Status <em>*</em></span><select class="form-input" wire:model.live="package_status"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card package-preview-card">
                <span class="package-icon">{{ strtoupper(substr($package_name ?: $package->package_name, 0, 1)) }}</span>
                <div><strong>{{ $package_name ?: $package->package_name }}</strong><small>{{ $duration_months ?: 0 }} bulan · Rp {{ number_format((float) ($price ?: 0), 0, ',', '.') }}</small></div>
                <span class="package-status package-status-{{ $package_status }}"><i></i>{{ $package_status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
            </section>
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan paket</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $identityComplete ? 'is-complete' : '' }}"><i>{{ $identityComplete ? '✓' : '1' }}</i><span><strong>Informasi paket</strong><small>Nama paket membership</small></span></li>
                    <li class="{{ $pricingComplete ? 'is-complete' : '' }}"><i>{{ $pricingComplete ? '✓' : '2' }}</i><span><strong>Durasi dan harga</strong><small>Masa berlaku dan nominal</small></span></li>
                    <li class="{{ $statusComplete ? 'is-complete' : '' }}"><i>{{ $statusComplete ? '✓' : '3' }}</i><span><strong>Ketersediaan</strong><small>Status paket</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('packages.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>

