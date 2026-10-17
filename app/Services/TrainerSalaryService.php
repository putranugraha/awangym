<?php

namespace App\Services;

use App\Models\PersonalTrainer;
use Carbon\CarbonImmutable;

class TrainerSalaryService
{
    public const MONTHLY_RATE = 1000000;

    public function summarize(PersonalTrainer $trainer, string $month): array
    {
        $monthStart = CarbonImmutable::createFromFormat('!Y-m', $month);
        $monthEnd = $monthStart->addMonth();
        $subscriptions = $trainer->subscriptions()
            ->paid()
            ->where('subscription_status', '!=', 'cancelled')
            ->whereDate('start_date', '<', $monthEnd)
            ->whereDate('end_date', '>', $monthStart)
            ->with(['member.user', 'package', 'payment', 'trainerAddon.payment'])
            ->oldest('start_date')->oldest('subscription_id')->get();

        $members = [];
        $duplicateDays = 0;
        foreach ($subscriptions as $subscription) {
            $addon = $subscription->trainerAddon;
            $gymPayment = $subscription->payment;
            $payment = $addon ? $addon->payment : $gymPayment;
            if (! $payment || $payment->payment_status !== 'paid' || ! $payment->payment_date || ! $gymPayment?->payment_date) {
                continue;
            }

            $start = CarbonImmutable::parse($addon?->start_date ?? $subscription->start_date)->startOfDay();
            $end = CarbonImmutable::parse($addon?->end_date ?? $subscription->end_date)->startOfDay();
            // An extra trainer service must also fit inside the paid gym period.
            $eligibleStart = max($start, CarbonImmutable::parse($subscription->start_date)->startOfDay(),
                CarbonImmutable::parse($gymPayment->payment_date)->startOfDay(),
                CarbonImmutable::parse($payment->payment_date)->startOfDay(), $monthStart);
            $eligibleEnd = min($end, CarbonImmutable::parse($subscription->end_date)->startOfDay(), $monthEnd);
            if ($eligibleStart->gte($eligibleEnd)) {
                continue;
            }

            $memberId = $subscription->member_id;
            $members[$memberId] ??= [
                'member_id' => $memberId,
                'name' => $subscription->member->user->full_name,
                'code' => $subscription->member->member_code,
                'amount' => 0, 'dates' => [], 'segments' => [],
            ];

            // Anchor every cycle to the original start (31 Jan -> 28 Feb ->
            // 31 Mar), not to a previously shortened month.
            $offset = max(0, ($eligibleStart->year - $start->year) * 12 + $eligibleStart->month - $start->month - 1);
            while (($cycleStart = $start->addMonthsNoOverflow($offset))->lt($eligibleEnd)) {
                $cycleEnd = $start->addMonthsNoOverflow(++$offset);
                $from = max($cycleStart, $eligibleStart);
                $until = min($cycleEnd, $eligibleEnd);
                if ($from->gte($until)) {
                    continue;
                }

                $cycleDays = (int) $cycleStart->diffInDays($cycleEnd);
                $amount = 0;
                $dates = [];
                $skipped = 0;
                for ($day = $from; $day->lt($until); $day = $day->addDay()) {
                    $date = $day->toDateString();
                    if (isset($members[$memberId]['dates'][$date])) {
                        $duplicateDays++;
                        $skipped++;

                        continue;
                    }
                    $dayIndex = (int) $cycleStart->diffInDays($day);
                    // Differences of cumulative rounded amounts ensure that
                    // all calendar-month slices sum to exactly Rp1,000,000.
                    $amount += $this->cumulativeAmount($dayIndex + 1, $cycleDays)
                        - $this->cumulativeAmount($dayIndex, $cycleDays);
                    $members[$memberId]['dates'][$date] = true;
                    $dates[] = $date;
                }
                if ($dates === []) {
                    continue;
                }
                $members[$memberId]['amount'] += $amount;
                $members[$memberId]['segments'][] = [
                    'invoice' => $payment->invoice_number,
                    'package' => $subscription->package->package_name,
                    'source' => $addon ? 'Tambahan PT' : 'Paket dengan PT',
                    'period_start' => $start,
                    'period_end' => $end,
                    'cycle_start' => $cycleStart,
                    'cycle_end' => $cycleEnd,
                    'counted_from' => CarbonImmutable::parse($dates[0]),
                    'counted_until' => CarbonImmutable::parse($dates[array_key_last($dates)]),
                    'days' => count($dates),
                    'cycle_days' => $cycleDays,
                    'skipped_days' => $skipped,
                    'amount' => $amount,
                ];
            }
        }

        $rows = collect($members)->filter(fn ($member) => $member['segments'] !== [])
            ->map(function ($member) {
                $member['days'] = count($member['dates']);
                unset($member['dates']);

                return $member;
            })->sortBy('name')->values();

        return [
            'month' => $month,
            'period_label' => $monthStart->locale('id')->translatedFormat('F Y'),
            'monthly_rate' => self::MONTHLY_RATE,
            'total' => $rows->sum('amount'),
            'member_count' => $rows->count(),
            'service_days' => $rows->sum('days'),
            'duplicate_days' => $duplicateDays,
            'members' => $rows,
            'is_future' => $monthStart->isAfter(CarbonImmutable::today()->startOfMonth()),
        ];
    }

    private function cumulativeAmount(int $days, int $cycleDays): int
    {
        return intdiv(self::MONTHLY_RATE * $days + intdiv($cycleDays, 2), $cycleDays);
    }
}
