<?php

use App\Models\PaymentTransaction;
use App\Services\MembershipPaymentService;
use Livewire\Component;

new class extends Component
{
    public PaymentTransaction $transaction;

    public string $payment_method = 'cash';

    public string $payment_status = 'pending';

    public string $notes = '';

    public function mount(PaymentTransaction $transaction): void
    {
        $this->transaction = $transaction->load(['member.user', 'subscription.package']);
        $this->fill($transaction->only(['payment_method', 'payment_status']));
        $this->notes = $transaction->notes ?? '';
    }

    public function save(): void
    {
        $d = $this->validate(['payment_method' => ['required', 'in:cash,transfer,e_wallet'], 'payment_status' => ['required', 'in:pending,paid,failed,refunded'], 'notes' => ['nullable']]);
        $this->transaction->update([
            'payment_method' => $d['payment_method'],
            'notes' => $d['notes'],
        ]);

        $service = app(MembershipPaymentService::class);
        if ($d['payment_status'] === 'paid') {
            if ($this->transaction->payment_status !== 'paid') {
                $service->markAsPaid($this->transaction, auth()->id());
            }
        } else {
            $service->markAsUnpaid($this->transaction, $d['payment_status']);
        }
        session()->flash('success', 'Transaksi berhasil diperbarui.');
        $this->redirectRoute('transactions.index', navigate: true);
    }

};
?>

@php
    $paymentComplete = filled($payment_method) && filled($payment_status);
    $completedSections = $paymentComplete ? 2 : 1;
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">{{ $transaction->invoice_number }}</span><h1>Edit Transaksi</h1><p>Perbarui metode, status pembayaran, atau catatan transaksi.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('transactions.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card transaction-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Detail Transaksi</h2><p>Informasi utama transaksi tidak dapat diubah dari halaman ini.</p></div></div>
            <div class="transaction-edit-summary">
                <div class="member-cell"><span class="member-table-avatar">{{ $transaction->member->user->initials() }}</span><span><strong>{{ $transaction->member->user->full_name }}</strong><small>{{ $transaction->member->member_code }}</small></span></div>
                <dl>
                    <div><dt>Paket</dt><dd>{{ $transaction->subscription->package->package_name }}</dd></div>
                    @if($transaction->subscription->trainer)
                        <div><dt>Trainer</dt><dd>{{ $transaction->subscription->trainer->trainer_code }} — {{ $transaction->subscription->trainer->user->full_name }}</dd></div>
                    @endif
                    <div><dt>Nominal</dt><dd>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</dd></div>
                    <div><dt>Periode</dt><dd>{{ $transaction->subscription->start_date->format('d M Y') }} — {{ $transaction->subscription->end_date->format('d M Y') }}</dd></div>
                </dl>
            </div>
            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Status Pembayaran</h2><p>Atur metode dan status transaksi.</p></div></div>
            <div class="form-grid">
                <label><span>Metode <em>*</em></span><select class="form-input" wire:model.live="payment_method"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="e_wallet">E-wallet</option></select></label>
                <label><span>Status <em>*</em></span><select class="form-input" wire:model.live="payment_status">@foreach(['pending','paid','failed','refunded'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></label>
            </div>
            <label><span>Catatan</span><textarea class="form-input" wire:model="notes" rows="3" placeholder="Catatan transaksi (opsional)"></textarea></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan transaksi</span><strong>{{ $completedSections }}/2 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 2) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="is-complete"><i>✓</i><span><strong>Detail transaksi</strong><small>Member, paket, dan nominal</small></span></li>
                    <li class="{{ $paymentComplete ? 'is-complete' : '' }}"><i>{{ $paymentComplete ? '✓' : '2' }}</i><span><strong>Status pembayaran</strong><small>Metode dan status terbaru</small></span></li>
                </ul>
            </section>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Simpan Perubahan</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('transactions.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>

