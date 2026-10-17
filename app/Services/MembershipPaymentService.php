<?php

namespace App\Services;

use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipPaymentService
{
    public function markAsPaid(PaymentTransaction $transaction, int $verifiedBy): void
    {
        DB::transaction(function () use ($transaction, $verifiedBy) {
            $transaction = PaymentTransaction::query()
                ->with('subscription.package')
                ->lockForUpdate()
                ->findOrFail($transaction->transaction_id);

            if ($transaction->trainer_addon_id) {
                if ($transaction->payment_status === 'paid') {
                    return;
                }
                app(TrainerAddonService::class)->activate($transaction);
                $transaction->update(['payment_status' => 'paid', 'payment_date' => now(), 'verified_by' => $verifiedBy]);

                return;
            }

            $subscription = $transaction->subscription;
            // A PT add-on belongs to this exact gym period. Re-verification
            // must not move that period underneath the existing add-on.
            if ($subscription->trainerAddon()->exists()) {
                $subscription->update(['subscription_status' => 'active']);
                $transaction->update(['payment_status' => 'paid', 'payment_date' => now(), 'verified_by' => $verifiedBy]);

                return;
            }
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
            $transaction = PaymentTransaction::query()
                ->lockForUpdate()
                ->findOrFail($transaction->transaction_id);
            if ($transaction->trainer_addon_id) {
                app(TrainerAddonService::class)->deactivate($transaction);
            }
            $transaction->update([
                'payment_status' => $status,
                'payment_date' => null,
                'verified_by' => null,
            ]);
        });
    }

    public function deleteUnpaid(PaymentTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = PaymentTransaction::query()->lockForUpdate()->findOrFail($transaction->transaction_id);
            if (in_array($transaction->payment_status, ['paid', 'refunded'], true)) {
                throw ValidationException::withMessages(['transaction' => 'Transaksi paid atau refunded tidak dapat dihapus.']);
            }
            $subscription = MembershipSubscription::query()->lockForUpdate()->findOrFail($transaction->subscription_id);
            if ($transaction->trainer_addon_id) {
                if ($subscription->trainerSessions()->exists()) {
                    throw ValidationException::withMessages(['transaction' => 'Tagihan PT dengan riwayat pertemuan tidak dapat dihapus.']);
                }
                $transaction->trainerAddon->delete();

                return;
            }
            if ($subscription->trainerAddon()->exists()) {
                throw ValidationException::withMessages(['transaction' => 'Transaksi gym dengan tagihan tambahan PT tidak dapat dihapus.']);
            }
            $subscription->delete();
        });
    }
}
