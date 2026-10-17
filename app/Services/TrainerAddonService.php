<?php

namespace App\Services;

use App\Models\MembershipSubscription;
use App\Models\MembershipTrainerAddon;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainerAddonService
{
    public const MONTHLY_RATE = 1800000;

    public function create(PaymentTransaction $basePayment, array $input, int $createdBy): PaymentTransaction
    {
        $data = Validator::make($input, [
            'trainer_id' => ['required', 'integer', 'exists:personal_trainers,trainer_id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'session_limit' => ['required', 'integer', 'min:1', 'max:'.max(1, (int) ($input['duration_months'] ?? 0) * 12)],
            'payment_method' => ['required', 'in:cash,transfer,e_wallet'],
            'payment_status' => ['required', 'in:pending,paid'],
        ])->validate();

        return DB::transaction(function () use ($basePayment, $data, $createdBy) {
            $basePayment = PaymentTransaction::query()->lockForUpdate()->findOrFail($basePayment->transaction_id);
            $subscription = MembershipSubscription::query()->lockForUpdate()->findOrFail($basePayment->subscription_id);
            if ($basePayment->trainer_addon_id || $basePayment->payment_status !== 'paid'
                || ! MembershipSubscription::current()->whereKey($subscription->subscription_id)->exists()) {
                throw ValidationException::withMessages(['addon' => 'Tambahan PT hanya tersedia pada transaksi gym lunas dengan membership aktif.']);
            }
            if ($subscription->trainer_id || $subscription->trainer_session_limit || $subscription->trainerAddon()->exists()) {
                throw ValidationException::withMessages(['addon' => 'Membership ini sudah memiliki layanan atau tagihan PT. Buka tagihan yang sudah ada.']);
            }
            $this->validateTrainer((int) $data['trainer_id']);
            $start = Carbon::parse($data['start_date']);
            $end = $start->copy()->addMonthsNoOverflow((int) $data['duration_months']);
            if ($start->lt($subscription->start_date) || $end->gt($subscription->end_date)) {
                throw ValidationException::withMessages(['addon' => 'Periode PT harus berada dalam masa berlaku gym. Pilih durasi lebih pendek atau tanggal mulai lebih awal.']);
            }

            $addon = MembershipTrainerAddon::create([
                'subscription_id' => $subscription->subscription_id,
                'trainer_id' => $data['trainer_id'],
                'created_by' => $createdBy,
                'start_date' => $start,
                'end_date' => $end,
                'duration_months' => $data['duration_months'],
                'session_limit' => $data['session_limit'],
                'monthly_rate' => self::MONTHLY_RATE,
            ]);
            $payment = PaymentTransaction::create([
                'invoice_number' => 'INV-PT-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'member_id' => $subscription->member_id,
                'subscription_id' => $subscription->subscription_id,
                'trainer_addon_id' => $addon->trainer_addon_id,
                'amount' => self::MONTHLY_RATE * (int) $data['duration_months'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'created_at' => now(),
            ]);
            if ($data['payment_status'] === 'paid') {
                app(MembershipPaymentService::class)->markAsPaid($payment, $createdBy);
            }

            return $payment->fresh();
        });
    }

    // Called inside the payment service's database transaction.
    public function activate(PaymentTransaction $payment): void
    {
        $subscription = MembershipSubscription::query()->lockForUpdate()->findOrFail($payment->subscription_id);
        $addon = $payment->trainerAddon;
        if (! $addon || $addon->subscription_id !== $subscription->subscription_id
            || ! MembershipSubscription::current()->whereKey($subscription->subscription_id)->exists()
            || $addon->end_date->lt(today()) || $addon->start_date->lt($subscription->start_date)
            || $addon->end_date->gt($subscription->end_date)) {
            throw ValidationException::withMessages(['payment_status' => 'Tambahan PT tidak dapat diaktifkan: periksa pembayaran gym dan periode layanan.']);
        }
        $this->validateTrainer((int) $addon->trainer_id);
        $subscription->update(['trainer_id' => $addon->trainer_id, 'trainer_session_limit' => $addon->session_limit]);
    }

    public function deactivate(PaymentTransaction $payment): void
    {
        MembershipSubscription::query()->lockForUpdate()->findOrFail($payment->subscription_id)
            ->update(['trainer_id' => null, 'trainer_session_limit' => null]);
    }

    private function validateTrainer(int $trainerId): void
    {
        if (! PersonalTrainer::whereKey($trainerId)->where('employment_status', 'active')
            ->whereHas('user', fn ($query) => $query->where('account_status', 'active'))->exists()) {
            throw ValidationException::withMessages(['trainer_id' => 'Pilih trainer dengan akun dan status kepegawaian aktif.']);
        }
    }
}
