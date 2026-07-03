<?php

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Services\MembershipPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'member')]
    public int|string $member_id = '';

    public int|string $package_id = '';

    public string $payment_method = '';

    public string $payment_status = '';

    public string $start_date = '';

    public string $notes = '';

    public function save(): void
    {
        $d = $this->validate(['member_id' => ['required', 'exists:members,member_id'], 'package_id' => ['required', 'exists:membership_packages,package_id'], 'payment_method' => ['required', 'in:cash,transfer,e_wallet'], 'payment_status' => ['required', 'in:pending,paid'], 'start_date' => ['required', 'date'], 'notes' => ['nullable']]);
        DB::transaction(function () use ($d) {
            $p = MembershipPackage::findOrFail($d['package_id']);
            $requestedStart = Carbon::parse($d['start_date']);
            $lastPaid = MembershipSubscription::where('member_id', $d['member_id'])
                ->paid()
                ->where('subscription_status', '!=', 'cancelled')
                ->latest('end_date')
                ->lockForUpdate()
                ->first();
            $start = $lastPaid && $lastPaid->end_date->gte($requestedStart)
                ? $lastPaid->end_date->copy()->addDay()
                : $requestedStart;
            $s = MembershipSubscription::create([...$d, 'created_by' => auth()->id(), 'subscription_type' => $lastPaid ? 'renewal' : 'new_registration', 'start_date' => $start, 'end_date' => $start->copy()->addMonthsNoOverflow($p->duration_months)->subDay(), 'subscription_status' => 'active']);
            $transaction = PaymentTransaction::create([...$d, 'subscription_id' => $s->subscription_id, 'amount' => $p->price, 'invoice_number' => 'INV-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)), 'payment_status' => 'pending', 'payment_date' => null, 'verified_by' => null, 'created_at' => now()]);

            if ($d['payment_status'] === 'paid') {
                app(MembershipPaymentService::class)->markAsPaid($transaction, auth()->id());
            }
        });
        session()->flash('success', 'Transaksi berhasil dibuat.');
        $this->redirectRoute('transactions.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'members' => Member::with('user')->get(),
            'packages' => MembershipPackage::where('package_status', 'active')->get(),
        ];
    }
};
?>

@php
    $selectionComplete = filled($member_id) && filled($package_id);
    $paymentComplete = filled($payment_method) && filled($payment_status);
    $periodComplete = filled($start_date);
    $completedSections = collect([$selectionComplete, $paymentComplete, $periodComplete])->filter()->count();
@endphp

<div class="awan-page">
    <header class="form-page-header">
        <div><span class="eyebrow">TRANSAKSI BARU</span><h1>Buat Transaksi</h1><p>Pilih member dan paket untuk membuat periode membership.</p></div>
        <a class="secondary-btn member-back-desktop" href="{{ route('transactions.index') }}" wire:navigate>Kembali</a>
    </header>
    <form wire:submit="save" class="form-layout">
        <section class="form-card transaction-form-main">
            <div class="form-section-title"><span>01</span><div><h2>Member dan Paket</h2><p>Tentukan penerima dan paket membership.</p></div></div>
            <label><span>Member <em>*</em></span><select class="form-input" wire:model.live="member_id"><option value="">Pilih member</option>@foreach($members as $m)<option value="{{ $m->member_id }}">{{ $m->member_code }} — {{ $m->user->full_name }}</option>@endforeach</select></label>
            <label><span>Paket membership <em>*</em></span><select class="form-input" wire:model.live="package_id"><option value="">Pilih paket</option>@foreach($packages as $p)<option value="{{ $p->package_id }}">{{ $p->package_name }} — Rp {{ number_format($p->price, 0, ',', '.') }}</option>@endforeach</select></label>
            <div class="form-section-title member-section-gap"><span>02</span><div><h2>Pembayaran</h2><p>Tentukan metode dan status pembayaran awal.</p></div></div>
            <div class="form-grid">
                <label><span>Metode <em>*</em></span><select class="form-input" wire:model.live="payment_method"><option value="">Pilih metode</option><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="e_wallet">E-wallet</option></select></label>
                <label><span>Status <em>*</em></span><select class="form-input" wire:model.live="payment_status"><option value="">Pilih status</option><option value="pending">Pending</option><option value="paid">Paid</option></select></label>
            </div>
            <div class="form-section-title member-section-gap"><span>03</span><div><h2>Periode dan Catatan</h2><p>Tentukan tanggal mulai membership.</p></div></div>
            <label><span>Tanggal mulai <em>*</em></span><input class="form-input" type="date" wire:model.live="start_date"></label>
            <label><span>Catatan</span><textarea class="form-input" wire:model="notes" rows="3" placeholder="Catatan transaksi (opsional)"></textarea></label>
        </section>
        <aside class="form-side-stack">
            <section class="form-card member-progress-card">
                <div class="member-progress-head"><div><span>Kelengkapan transaksi</span><strong>{{ $completedSections }}/3 bagian</strong></div><div class="member-progress-track"><i style="width: {{ ($completedSections / 3) * 100 }}%"></i></div></div>
                <ul class="member-checklist">
                    <li class="{{ $selectionComplete ? 'is-complete' : '' }}"><i>{{ $selectionComplete ? '✓' : '1' }}</i><span><strong>Member dan paket</strong><small>Penerima membership</small></span></li>
                    <li class="{{ $paymentComplete ? 'is-complete' : '' }}"><i>{{ $paymentComplete ? '✓' : '2' }}</i><span><strong>Pembayaran</strong><small>Metode dan status</small></span></li>
                    <li class="{{ $periodComplete ? 'is-complete' : '' }}"><i>{{ $periodComplete ? '✓' : '3' }}</i><span><strong>Periode</strong><small>Tanggal mulai membership</small></span></li>
                </ul>
            </section>
            <div class="member-form-note"><strong>Status pembayaran</strong><p>Membership hanya aktif setelah transaksi berstatus paid.</p></div>
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif
            <button class="primary-btn form-submit" wire:loading.attr="disabled"><span wire:loading.remove>Buat Transaksi</span><span wire:loading>Menyimpan…</span></button>
            <a class="secondary-btn member-back-mobile" href="{{ route('transactions.index') }}" wire:navigate>Kembali</a>
        </aside>
    </form>
</div>

