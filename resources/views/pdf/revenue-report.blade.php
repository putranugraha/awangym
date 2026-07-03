<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pemasukan {{ $periodLabel }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #171719; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .header { border-bottom: 3px solid #e52335; padding-bottom: 14px; }
        .brand { color: #e52335; font-size: 11px; font-weight: bold; letter-spacing: 2px; }
        h1 { margin: 5px 0 3px; font-size: 22px; }
        .muted { color: #6b7280; }
        .meta { float: right; margin-top: -39px; text-align: right; line-height: 1.6; }
        .summary { width: 100%; margin: 18px 0; border-collapse: separate; border-spacing: 8px 0; }
        .summary td { width: 25%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 7px; background: #f8f9fa; }
        .summary td:first-child { color: white; border-color: #e52335; background: #e52335; }
        .summary span { display: block; margin-bottom: 6px; font-size: 8px; font-weight: bold; letter-spacing: .5px; text-transform: uppercase; }
        .summary strong { font-size: 15px; }
        .section-title { margin: 0 0 9px; font-size: 13px; }
        table.transactions { width: 100%; border-collapse: collapse; }
        .transactions th { padding: 9px 8px; color: #ffffff; background: #252529; font-size: 8px; letter-spacing: .4px; text-align: left; text-transform: uppercase; }
        .transactions td { padding: 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .transactions tbody tr:nth-child(even) { background: #f8f9fa; }
        .right { text-align: right !important; }
        .invoice { color: #b51628; font-weight: bold; }
        .member { font-weight: bold; }
        .amount { font-weight: bold; white-space: nowrap; }
        .empty { padding: 32px; border: 1px solid #e5e7eb; color: #6b7280; text-align: center; }
        .footer { margin-top: 16px; padding-top: 9px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 8px; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand">AWAN GYM</div>
        <h1>Laporan Pemasukan</h1>
        <div class="muted">Transaksi membership berstatus paid · {{ $periodLabel }}</div>
        <div class="meta">
            <strong>Periode {{ $periodLabel }}</strong><br>
            Dicetak {{ $generatedAt->translatedFormat('d F Y, H:i') }}
        </div>
    </header>

    <table class="summary">
        <tr>
            <td><span>Total pemasukan</span><strong>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></td>
            <td><span>Transaksi paid</span><strong>{{ $transactions->count() }} transaksi</strong></td>
            <td><span>Rata-rata transaksi</span><strong>Rp {{ number_format($averageRevenue, 0, ',', '.') }}</strong></td>
            <td><span>Transaksi terbesar</span><strong>Rp {{ number_format($largestRevenue, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <h2 class="section-title">Rincian Transaksi Terverifikasi</h2>
    @if($transactions->isEmpty())
        <div class="empty">Tidak ada transaksi paid pada periode {{ $periodLabel }}.</div>
    @else
        <table class="transactions">
            <thead>
                <tr>
                    <th style="width:4%">No.</th>
                    <th style="width:17%">Invoice</th>
                    <th style="width:20%">Member</th>
                    <th style="width:18%">Paket</th>
                    <th style="width:12%">Tanggal Bayar</th>
                    <th style="width:10%">Metode</th>
                    <th style="width:19%" class="right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="invoice">{{ $transaction->invoice_number }}</td>
                        <td><span class="member">{{ $transaction->member->user->full_name }}</span><br><span class="muted">{{ $transaction->member->member_code }}</span></td>
                        <td>{{ $transaction->subscription->package->package_name }}</td>
                        <td>{{ $transaction->payment_date->translatedFormat('d M Y') }}<br><span class="muted">{{ $transaction->payment_date->format('H:i') }}</span></td>
                        <td>{{ match($transaction->payment_method) {'cash' => 'Tunai', 'e_wallet' => 'E-wallet', default => 'Transfer'} }}</td>
                        <td class="right amount">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <footer class="footer">
        Laporan ini dibuat otomatis dari data transaksi terverifikasi Awan Gym.
        <span class="footer-right">Total {{ $transactions->count() }} transaksi · Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
    </footer>
</body>
</html>
