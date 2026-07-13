<?php

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\MembershipPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function membershipFixture(string $paymentStatus = 'pending', ?string $startDate = null): array
{
    $admin = User::factory()->create();
    $memberUser = User::factory()->create();
    $member = Member::create([
        'user_id' => $memberUser->user_id,
        'member_code' => 'AGM-'.fake()->unique()->numerify('#####'),
        'gender' => 'L',
        'birth_date' => '2000-01-01',
        'address' => 'Alamat pengujian',
        'registered_at' => today(),
    ]);
    $package = MembershipPackage::create([
        'package_name' => 'Paket 30 Hari',
        'duration_months' => 1,
        'price' => 300000,
        'package_status' => 'active',
    ]);
    $subscription = MembershipSubscription::create([
        'member_id' => $member->member_id,
        'package_id' => $package->package_id,
        'created_by' => $admin->user_id,
        'subscription_type' => 'new_registration',
        'start_date' => $startDate ?? today(),
        'end_date' => now()->parse($startDate ?? today())->addDays(29),
        'subscription_status' => 'active',
    ]);
    $transaction = PaymentTransaction::create([
        'invoice_number' => 'INV-'.fake()->unique()->numerify('########'),
        'member_id' => $member->member_id,
        'subscription_id' => $subscription->subscription_id,
        'amount' => $package->price,
        'payment_method' => 'cash',
        'payment_status' => $paymentStatus,
        'payment_date' => $paymentStatus === 'paid' ? now() : null,
        'verified_by' => $paymentStatus === 'paid' ? $admin->user_id : null,
        'created_at' => now(),
    ]);

    return compact('admin', 'member', 'package', 'subscription', 'transaction');
}

test('pending subscription is not considered current membership', function () {
    $fixture = membershipFixture();

    expect(MembershipSubscription::current()->find($fixture['subscription']->subscription_id))->toBeNull();
});

test('verifying payment places its period after the latest paid subscription', function () {
    $current = membershipFixture('paid');
    $nextSubscription = MembershipSubscription::create([
        'member_id' => $current['member']->member_id,
        'package_id' => $current['package']->package_id,
        'created_by' => $current['admin']->user_id,
        'subscription_type' => 'renewal',
        'start_date' => today(),
        'end_date' => today()->addDays(29),
        'subscription_status' => 'active',
    ]);
    $pending = PaymentTransaction::create([
        'invoice_number' => 'INV-NEXT-001',
        'member_id' => $current['member']->member_id,
        'subscription_id' => $nextSubscription->subscription_id,
        'amount' => $current['package']->price,
        'payment_method' => 'transfer',
        'payment_status' => 'pending',
        'created_at' => now(),
    ]);

    app(MembershipPaymentService::class)->markAsPaid($pending, $current['admin']->user_id);

    expect($pending->fresh())
        ->payment_status->toBe('paid')
        ->payment_date->not->toBeNull()
        ->and($nextSubscription->fresh()->start_date->toDateString())
        ->toBe($current['subscription']->end_date->copy()->addDay()->toDateString())
        ->and($nextSubscription->fresh()->end_date->toDateString())
        ->toBe($current['subscription']->end_date->copy()->addDay()->addMonthNoOverflow()->toDateString());
});

test('changing a paid transaction to unpaid clears verification metadata', function () {
    $fixture = membershipFixture('paid');

    app(MembershipPaymentService::class)->markAsUnpaid($fixture['transaction'], 'refunded');

    expect($fixture['transaction']->fresh())
        ->payment_status->toBe('refunded')
        ->payment_date->toBeNull()
        ->verified_by->toBeNull();
});
