<?php

namespace App\Services;

use App\Models\MembershipSubscription;
use Illuminate\Support\Carbon;

class MembershipStatusService
{
    public function resolve(?MembershipSubscription $subscription): array
    {
        if (! $subscription) {
            return ['key' => 'inactive', 'label' => 'Tidak Aktif', 'days_left' => 0];
        }
        if ($subscription->subscription_status === 'cancelled') {
            return ['key' => 'cancelled', 'label' => 'Dibatalkan', 'days_left' => 0];
        }

        $paid = $subscription->payment?->payment_status === 'paid';
        $today = Carbon::today();
        if (! $paid || $today->lt($subscription->start_date)) {
            return ['key' => 'inactive', 'label' => 'Tidak Aktif', 'days_left' => 0];
        }
        if ($today->gt($subscription->end_date)) {
            return ['key' => 'expired', 'label' => 'Kedaluwarsa', 'days_left' => 0];
        }

        $days = $today->diffInDays($subscription->end_date);

        return $days <= 7
            ? ['key' => 'expiring', 'label' => 'Segera Berakhir', 'days_left' => $days]
            : ['key' => 'active', 'label' => 'Aktif', 'days_left' => $days];
    }
}
