<x-layouts::app title="Laporan"><div class="awan-page">
<div class="page-heading"><div><span class="eyebrow">KEUANGAN</span><h1>Laporan Pemasukan</h1></div></div>
<form><label>Periode<input class="form-input" type="month" name="month" value="{{ $month }}" onchange="this.form.submit()"></label></form>
<article class="metric-card accent"><span>Total pemasukan terverifikasi</span><strong class="currency">Rp {{ number_format($transactions->sum('amount'),0,',','.') }}</strong><small>{{ $transactions->count() }} transaksi paid</small></article>
@foreach($transactions as $t)<article class="list-card"><div class="grow"><h3>{{ $t->member->user->full_name }}</h3><p>{{ $t->invoice_number }} Â· {{ $t->payment_date->format('d M Y') }}</p></div><strong>Rp {{ number_format($t->amount,0,',','.') }}</strong></article>@endforeach
</div></x-layouts::app>

