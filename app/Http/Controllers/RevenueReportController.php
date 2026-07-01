<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

class RevenueReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = now()->createFromFormat('Y-m', $month)->startOfMonth();
        $transactions = PaymentTransaction::with('member.user')
            ->where('payment_status', 'paid')
            ->whereBetween('payment_date', [$start, $start->copy()->endOfMonth()])
            ->latest('payment_date')
            ->get();

        return view('reports.index', compact('transactions', 'month'));
    }
}
