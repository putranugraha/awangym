<?php

namespace App\Http\Controllers;

use App\Models\MembershipSubscription;
use App\Models\User;
use App\Services\MembershipStatusService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __invoke(Request $request, MembershipStatusService $statusService): View
    {
        /** @var User $user */
        $user = $request->user();

        return $this->renderProfile($user, $statusService, true);
    }

    public function show(User $user, MembershipStatusService $statusService): View
    {
        abort_unless(auth()->user()->can('manage users'), 403);

        return $this->renderProfile($user, $statusService, false);
    }

    private function renderProfile(User $user, MembershipStatusService $statusService, bool $isOwnProfile): View
    {
        $user->load(['roles', 'member', 'personalTrainer']);

        $member = $user->hasRole('member') ? $user->member : null;
        $trainer = $user->hasRole('personal_trainer') ? $user->personalTrainer : null;
        $subscription = null;
        $upcomingSubscription = null;
        $pendingPayments = 0;
        $trainerStats = null;

        if ($member) {
            $subscriptions = $member->subscriptions()
                ->with(['package', 'payment', 'trainer.user'])
                ->withCount('trainerSessions');

            $currentSubscription = (clone $subscriptions)->current()->latest('end_date')->first();
            $upcomingSubscription = (clone $subscriptions)
                ->paid()
                ->where('subscription_status', 'active')
                ->whereDate('start_date', '>', today())
                ->oldest('start_date')->first();
            $subscription = $currentSubscription ?? $upcomingSubscription
                ?? (clone $subscriptions)->latest('end_date')->latest('subscription_id')->first();

            $pendingPayments = $member->payments()->where('payment_status', 'pending')->count();
        }

        if ($trainer) {
            $activeSubscriptions = $trainer->subscriptions()->currentTrainer()->withCount('trainerSessions')->get();
            $trainerStats = [
                'members' => $activeSubscriptions->pluck('member_id')->unique()->count(),
                'sessions' => $trainer->sessions()->count(),
                'remaining' => $activeSubscriptions->sum(fn ($item) => max(
                    ($item->trainer_session_limit ?? 0) - $item->trainer_sessions_count, 0
                )),
            ];
        }

        return view('profile.show', [
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
            'member' => $member,
            'trainer' => $trainer,
            'subscription' => $subscription,
            'upcomingSubscription' => $upcomingSubscription,
            'pendingPayments' => $pendingPayments,
            'membershipStatus' => $this->membershipStatus($subscription, $statusService),
            'trainerStats' => $trainerStats,
            'permissions' => $user->hasRole('admin') ? $user->getAllPermissions()->sortBy('name') : collect(),
        ]);
    }

    /** @return array{key: string, label: string, days_left: int|float} */
    private function membershipStatus(?MembershipSubscription $subscription, MembershipStatusService $service): array
    {
        if ($subscription && $subscription->subscription_status !== 'cancelled') {
            $status = match ($subscription->payment?->payment_status) {
                'pending' => ['pending', 'Menunggu pembayaran'],
                'failed' => ['failed', 'Pembayaran gagal'],
                'refunded' => ['refunded', 'Pembayaran dikembalikan'],
                'paid' => $subscription->start_date->isFuture() ? ['upcoming', 'Belum dimulai'] : null,
                default => null,
            };

            if ($status) {
                return ['key' => $status[0], 'label' => $status[1], 'days_left' => 0];
            }
        }

        return $service->resolve($subscription);
    }
}
