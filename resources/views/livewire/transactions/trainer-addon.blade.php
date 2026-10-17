<?php

use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Services\TrainerAddonService;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
{
    public PaymentTransaction $transaction;
    public string $trainer_id = '';
    public string $start_date = '';
    public int|string $duration_months = 1;
    public int|string $session_limit = 12;
    public string $payment_method = 'cash';
    public string $payment_status = 'pending';

    public function mount(PaymentTransaction $transaction): void
    {
        abort_unless(auth()->user()->can('manage payments'), 403);
        $this->transaction = $transaction;
        $this->start_date = today()->toDateString();
    }

    public function updatedDurationMonths(): void
    {
        $this->session_limit = max(1, min(60, (int) $this->duration_months)) * 12;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('manage payments'), 403);
        $this->resetErrorBag();
        $payment = app(TrainerAddonService::class)->create($this->transaction, [
            'trainer_id' => $this->trainer_id, 'start_date' => $this->start_date,
            'duration_months' => $this->duration_months, 'session_limit' => $this->session_limit,
            'payment_method' => $this->payment_method, 'payment_status' => $this->payment_status,
        ], auth()->id());
        session()->flash('success', 'Tagihan tambahan PT berhasil dibuat. Pembayaran dan periode gym tetap.');
        $this->redirectRoute('transactions.edit', $payment, navigate: true);
    }

    public function with(): array
    {
        $payment = $this->transaction->fresh(['subscription.payment', 'subscription.trainerAddon.payment', 'subscription.trainerAddon.trainer.user']);
        $subscription = $payment->subscription;
        $addon = $subscription->trainerAddon;
        $eligible = ! $payment->trainer_addon_id && $payment->payment_status === 'paid'
            && $subscription->subscription_status === 'active'
            && today()->betweenIncluded($subscription->start_date, $subscription->end_date)
            && ! $subscription->trainer_id && ! $subscription->trainer_session_limit && ! $addon;
        $end = null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->start_date)
            && checkdate((int) substr($this->start_date, 5, 2), (int) substr($this->start_date, 8, 2), (int) substr($this->start_date, 0, 4))
            && (int) $this->duration_months >= 1 && (int) $this->duration_months <= 60) {
            $end = Carbon::parse($this->start_date)->addMonthsNoOverflow((int) $this->duration_months);
        }

        return [
            'subscription' => $subscription, 'addon' => $addon, 'eligible' => $eligible, 'estimatedEnd' => $end,
            'fitsPeriod' => $end && $end->lte($subscription->end_date) && $this->start_date >= today()->toDateString(),
            'monthlyRate' => TrainerAddonService::MONTHLY_RATE,
            'additionalAmount' => TrainerAddonService::MONTHLY_RATE * max(0, min(60, (int) $this->duration_months)),
            'trainers' => $eligible ? PersonalTrainer::with('user')->where('employment_status', 'active')
                ->whereHas('user', fn ($query) => $query->where('account_status', 'active'))->get() : collect(),
        ];
    }
};
?>

<section class="pt-addon-panel" aria-labelledby="pt-addon-heading">
    <div class="pt-addon-header"><div><span class="eyebrow">LAYANAN TAMBAHAN</span><h2 id="pt-addon-heading">Personal Trainer</h2><p>Tambahkan pendampingan pada membership yang sudah berjalan.</p></div>@if($transaction->payment_status === 'paid')<span class="pt-addon-chip">Gym tetap lunas</span>@endif</div>
    @if($addon)
        <div class="pt-addon-existing">
            <div><strong>{{ $addon->trainer->user->full_name }}</strong><p>{{ $addon->start_date->translatedFormat('d M Y') }} – {{ $addon->end_date->translatedFormat('d M Y') }} · {{ $addon->session_limit }} sesi</p><p>{{ $addon->payment->invoice_number }} · {{ strtoupper($addon->payment->payment_status) }} · Rp {{ number_format($addon->payment->amount, 0, ',', '.') }}</p></div>
            <a class="primary-btn" href="{{ route('transactions.edit', $addon->payment) }}" wire:navigate>Buka Tagihan PT</a>
        </div>
    @elseif($eligible)
        <form wire:submit="save" class="pt-addon-layout">
            <div class="pt-addon-fields">
                <div class="form-grid">
                    <label><span>Trainer pendamping <em>*</em></span><select class="form-input" wire:model="trainer_id"><option value="">Pilih trainer</option>@foreach($trainers as $trainer)<option value="{{ $trainer->trainer_id }}">{{ $trainer->user->full_name }} · {{ $trainer->trainer_code }}</option>@endforeach</select></label>
                    <label><span>Tanggal mulai PT <em>*</em></span><input class="form-input" type="date" min="{{ today()->toDateString() }}" max="{{ $subscription->end_date->toDateString() }}" wire:model.live="start_date"></label>
                    <label><span>Durasi PT (bulan) <em>*</em></span><input class="form-input" type="number" min="1" max="60" step="1" wire:model.live="duration_months"></label>
                    <label><span>Kuota pertemuan <em>*</em></span><input class="form-input" type="number" min="1" max="{{ max(1, (int) $duration_months * 12) }}" step="1" wire:model="session_limit"><small>Maksimal 12 sesi per bulan. Mengurangi kuota tidak mengurangi tarif bulanan.</small></label>
                    <label><span>Metode pembayaran <em>*</em></span><select class="form-input" wire:model="payment_method"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="e_wallet">E-wallet</option></select></label>
                    <label><span>Status pembayaran PT <em>*</em></span><select class="form-input" wire:model="payment_status"><option value="pending">Menunggu pembayaran</option><option value="paid">Sudah lunas</option></select></label>
                </div>
                <p class="pt-addon-help">Pembelian PT dihitung per bulan penuh. Periode PT tidak boleh melewati akhir membership. Layanan ini membutuhkan sisa masa aktif minimal satu bulan penuh.</p>
                @if($errors->any())<div class="error-box" role="alert">{{ $errors->first() }}</div>@endif
            </div>
            <aside class="pt-addon-summary">
                <span class="eyebrow">RINGKASAN TAMBAHAN</span>
                <dl><div><dt>Pembayaran gym</dt><dd>Sudah lunas</dd></div><div><dt>Gym berlaku sampai</dt><dd>{{ $subscription->end_date->translatedFormat('d M Y') }}</dd></div><div><dt>PT berlaku sampai</dt><dd>{{ $estimatedEnd?->translatedFormat('d M Y') ?? '—' }}</dd></div><div><dt>Tarif PT per bulan</dt><dd>Rp {{ number_format($monthlyRate, 0, ',', '.') }}</dd></div></dl>
                <div class="pt-addon-total"><span>Tagihan tambahan PT</span><strong>Rp {{ number_format($additionalAmount, 0, ',', '.') }}</strong><small>Hanya biaya PT untuk {{ (int) $duration_months }} bulan. Biaya gym tidak ditagih ulang.</small></div>
                @if(! $fitsPeriod)<p class="pt-addon-warning">Periksa tanggal dan durasi PT. Seluruh periode harus masuk dalam masa berlaku gym.</p>@endif
                <button class="primary-btn" wire:loading.attr="disabled" @disabled(! $fitsPeriod)><span wire:loading.remove>Buat Tagihan Tambahan PT</span><span wire:loading>Menyimpan…</span></button>
            </aside>
        </form>
    @else
        <p class="pt-addon-help">Tambahan PT tersedia untuk membership gym mandiri yang sudah lunas dan masih aktif. Paket yang sudah memiliki PT tetap menggunakan layanan yang tercatat.</p>
    @endif
</section>
