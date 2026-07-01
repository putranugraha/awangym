<x-layouts::app title="Pembayaran"><div class="awan-page">
<div class="page-heading"><div><span class="eyebrow">TRANSAKSI</span><h1>Riwayat Pembayaran</h1></div></div>
@forelse($payments as $payment)<article class="list-card"><div class="grow"><h3>{{ $payment->invoice_number }}</h3><p>{{ $payment->subscription->package->package_name }} Â· {{ $payment->created_at->format('d M Y') }}</p></div><div class="text-right"><strong>Rp {{ number_format($payment->amount,0,',','.') }}</strong><br><span class="chip">{{ strtoupper($payment->payment_status) }}</span></div></article>@empty<div class="empty-card"><h2>Belum ada pembayaran</h2></div>@endforelse
{{ $payments->links() }}</div></x-layouts::app>

