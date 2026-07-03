<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class RevenueReportPdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);
        $month = $validated['month'];
        $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $transactions = PaymentTransaction::with(['member.user', 'subscription.package', 'verifier'])
            ->where('payment_status', 'paid')
            ->whereBetween('payment_date', [$start, $start->copy()->endOfMonth()])
            ->latest('payment_date')
            ->get();

        $pdf = Pdf::loadView('pdf.revenue-report', [
            'transactions' => $transactions,
            'periodLabel' => $start->translatedFormat('F Y'),
            'generatedAt' => now(),
            'totalRevenue' => $transactions->sum('amount'),
            'averageRevenue' => $transactions->avg('amount') ?? 0,
            'largestRevenue' => $transactions->max('amount') ?? 0,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("laporan-pemasukan-{$month}.pdf");
    }
}
