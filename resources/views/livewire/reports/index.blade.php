<?php

use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        if (! $this->isValidMonth($this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    public function updatedMonth(): void
    {
        $this->validateOnly('month', [
            'month' => ['required', 'date_format:Y-m'],
        ]);
    }

    public function with(): array
    {
        $month = $this->isValidMonth($this->month) ? $this->month : now()->format('Y-m');
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $transactions = PaymentTransaction::with('member.user')
            ->where('payment_status', 'paid')
            ->whereBetween('payment_date', [$start, $start->copy()->endOfMonth()])
            ->latest('payment_date')
            ->get();

        return [
            'transactions' => $transactions,
            'totalRevenue' => $transactions->sum('amount'),
            'averageRevenue' => $transactions->avg('amount') ?? 0,
            'largestRevenue' => $transactions->max('amount') ?? 0,
            'periodLabel' => $start->translatedFormat('F Y'),
        ];
    }

    private function isValidMonth(string $month): bool
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return false;
        }

        return Carbon::createFromFormat('!Y-m', $month)->format('Y-m') === $month;
    }
};
?>

<div class="awan-page">
    <header class="resource-header">
        <div><span class="eyebrow">KEUANGAN</span><h1>Laporan Pemasukan</h1><p>Pantau seluruh pemasukan dari transaksi membership terverifikasi.</p></div>
        <a class="primary-btn" href="{{ route('reports.pdf', ['month' => $month]) }}">
            <span aria-hidden="true">↓</span> Print PDF
        </a>
    </header>

    @error('month')<div class="error-box">{{ $message }}</div>@enderror

    <div class="transaction-stats">
        <article class="transaction-stat transaction-stat-featured">
            <span>Total pemasukan</span>
            <strong>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong>
            <small>Transaksi terverifikasi</small>
        </article>
        <article>
            <span>Transaksi paid</span>
            <strong>{{ $transactions->count() }}</strong>
            <small>{{ $periodLabel }}</small>
        </article>
        <article>
            <span>Rata-rata transaksi</span>
            <strong class="report-stat-currency">Rp {{ number_format($averageRevenue, 0, ',', '.') }}</strong>
            <small>Per transaksi paid</small>
        </article>
        <article>
            <span>Transaksi terbesar</span>
            <strong class="report-stat-currency">Rp {{ number_format($largestRevenue, 0, ',', '.') }}</strong>
            <small>{{ $periodLabel }}</small>
        </article>
    </div>

    <section class="data-panel">
        <div class="data-toolbar report-toolbar">
            <div><span class="eyebrow">RINCIAN</span><h2>Transaksi Terverifikasi</h2></div>
            <label class="report-period-filter"><span>Periode</span><input class="form-input" type="month" wire:model.live="month"></label>
        </div>

        <div class="responsive-table">
            <table class="member-table transaction-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Member</th>
                        <th>Tanggal Bayar</th>
                        <th>Metode</th>
                        <th>Nominal</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr wire:key="report-transaction-{{ $transaction->transaction_id }}">
                            <td data-label="Invoice"><span class="invoice-number">{{ $transaction->invoice_number }}</span></td>
                            <td data-label="Member">
                                <div class="member-cell">
                                    <span class="member-table-avatar">{{ $transaction->member->user->initials() }}</span>
                                    <span><strong>{{ $transaction->member->user->full_name }}</strong><small>{{ $transaction->member->member_code }}</small></span>
                                </div>
                            </td>
                            <td data-label="Tanggal Bayar"><span class="table-primary">{{ $transaction->payment_date->translatedFormat('d M Y') }}</span><small class="table-secondary">{{ $transaction->payment_date->format('H:i') }}</small></td>
                            <td data-label="Metode"><span class="payment-method payment-method-{{ $transaction->payment_method }}">{{ match($transaction->payment_method) {'cash' => 'Tunai', 'e_wallet' => 'E-wallet', default => 'Transfer'} }}</span></td>
                            <td data-label="Nominal"><strong class="transaction-amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</strong></td>
                            <td data-label="Aksi" class="action-column"><a href="{{ route('transactions.edit', $transaction) }}" class="table-action table-action-secondary" wire:navigate>Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><strong>Belum ada pemasukan</strong><p>Tidak ada transaksi paid pada periode {{ $periodLabel }}.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
