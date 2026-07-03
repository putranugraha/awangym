<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Services\MembershipStatusService;

class DashboardController extends Controller
{
    public function __invoke(MembershipStatusService $statusService)
    {
        $user = auth()->user();

        if ($user->hasRole('member')) {
            $member = $user->member()->with([
                'subscriptions' => fn ($q) => $q->with(['package', 'payment'])->latest('end_date'),
                'payments' => fn ($q) => $q->latest('created_at')->limit(3),
                'programs' => fn ($q) => $q->with(['program', 'trainer.user'])->where('program_status', 'active'),
            ])->firstOrFail();
            $subscription = $member->subscriptions()
                ->with(['package', 'payment'])
                ->current()
                ->latest('end_date')
                ->first();

            return view('dashboard', compact('member', 'subscription') + ['membershipStatus' => $statusService->resolve($subscription)]);
        }

        if ($user->hasRole('personal_trainer')) {
            $trainer = $user->personalTrainer;

            return view('dashboard', [
                'trainer' => $trainer,
                'activePrograms' => $trainer->memberPrograms()->where('program_status', 'active')->count(),
                'memberCount' => $trainer->memberPrograms()->where('program_status', 'active')->distinct('member_id')->count('member_id'),
            ]);
        }

        $revenueTrend = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = now()->subMonths($monthsAgo);

            return [
                'label' => $month->translatedFormat('M'),
                'value' => (float) PaymentTransaction::where('payment_status', 'paid')
                    ->whereBetween('payment_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount'),
            ];
        });
        $monthlyRevenue = $revenueTrend->last()['value'];
        $previousRevenue = $revenueTrend->slice(-2, 1)->first()['value'] ?? 0;
        $revenueGrowth = $previousRevenue > 0
            ? round((($monthlyRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : ($monthlyRevenue > 0 ? 100 : 0);

        $activeMembers = MembershipSubscription::where('subscription_status', 'active')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->whereHas('payment', fn ($query) => $query->where('payment_status', 'paid'))
            ->distinct('member_id')->count('member_id');
        $expiringMembers = MembershipSubscription::where('subscription_status', 'active')
            ->whereBetween('end_date', [today(), today()->addDays(7)])
            ->whereHas('payment', fn ($query) => $query->where('payment_status', 'paid'))
            ->distinct('member_id')->count('member_id');
        $inactiveMembers = max(Member::count() - $activeMembers, 0);

        return view('dashboard', [
            'activeMembers' => $activeMembers,
            'expiringMembers' => $expiringMembers,
            'inactiveMembers' => $inactiveMembers,
            'monthlyRevenue' => $monthlyRevenue,
            'revenueGrowth' => $revenueGrowth,
            'revenueChart' => [
                'labels' => $revenueTrend->pluck('label')->all(),
                'values' => $revenueTrend->pluck('value')->all(),
            ],
            'membershipChart' => [
                'labels' => ['Aktif', 'Segera berakhir', 'Tidak aktif'],
                'values' => [max($activeMembers - $expiringMembers, 0), $expiringMembers, $inactiveMembers],
            ],
            'pendingPayments' => PaymentTransaction::where('payment_status', 'pending')->count(),
            'recentMembers' => Member::with('user')->latest()->limit(5)->get(),
            'recentTransactions' => PaymentTransaction::with(['member.user', 'subscription.package'])
                ->latest('created_at')->limit(5)->get(),
        ]);
    }
}
