<?php

namespace App\Services;

use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class MembershipPaymentService
{
    public function markAsPaid(PaymentTransaction $transaction, int $verifiedBy): void
    {
        DB::transaction(function () use ($transaction, $verifiedBy) {
            $transaction = PaymentTransaction::query()
                ->with('subscription.package')
                ->lockForUpdate()
                ->findOrFail($transaction->transaction_id);

            $subscription = $transaction->subscription;
            $lastPaid = MembershipSubscription::query()
                ->where('member_id', $subscription->member_id)
                ->where('subscription_id', '!=', $subscription->subscription_id)
                ->paid()
                ->where('subscription_status', '!=', 'cancelled')
                ->latest('end_date')
                ->lockForUpdate()
                ->first();

            $start = $lastPaid && $lastPaid->end_date->gte($subscription->start_date)
                ? $lastPaid->end_date->copy()->addDay()
                : $subscription->start_date->copy();

            $subscription->update([
                'subscription_type' => $lastPaid ? 'renewal' : 'new_registration',
                'start_date' => $start,
                'end_date' => $start->copy()->addMonthsNoOverflow($subscription->package->duration_months),
                'subscription_status' => 'active',
            ]);

            $transaction->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
                'verified_by' => $verifiedBy,
            ]);
        });
    }

    public function markAsUnpaid(PaymentTransaction $transaction, string $status): void
    {
        DB::transaction(function () use ($transaction, $status) {
            PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->transaction_id)
                ->update([
                    'payment_status' => $status,
                    'payment_date' => null,
                    'verified_by' => null,
                ]);
        });
    }
}
