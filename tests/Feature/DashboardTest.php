<?php

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

trait DashboardDatabaseFunctions
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));
            DB::connection()->getPdo()->sqliteCreateFunction('CEIL', fn ($value) => ceil($value), 1);
        }
    }
}

uses(DashboardDatabaseFunctions::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard aggregates six calendar months in one revenue query', function () {
    $this->travelTo(now()->setDate(2026, 3, 31)->startOfDay());
    $user = User::factory()->create();
    $member = Member::create([
        'user_id' => $user->user_id,
        'member_code' => 'AGM-PERF',
        'gender' => 'L',
        'birth_date' => '2000-01-01',
        'address' => 'Alamat pengujian',
        'registered_at' => today(),
    ]);
    $package = MembershipPackage::create([
        'package_name' => 'Bulanan', 'duration_months' => 1,
        'price' => 100, 'package_status' => 'active',
    ]);
    $subscription = MembershipSubscription::create([
        'member_id' => $member->member_id, 'package_id' => $package->package_id,
        'created_by' => $user->user_id, 'subscription_type' => 'new_registration',
        'start_date' => today(), 'end_date' => today()->addMonth(),
        'subscription_status' => 'active',
    ]);

    foreach ([
        ['2025-09-30 23:59:59', 999, 'paid'],
        ['2025-10-01 00:00:00', 100, 'paid'],
        ['2026-02-28 23:59:59', 200, 'paid'],
        ['2026-03-01 00:00:00', 300, 'paid'],
        ['2026-03-31 23:59:59', 50, 'paid'],
        ['2026-03-15 00:00:00', 999, 'pending'],
        ['2026-04-01 00:00:00', 999, 'paid'],
    ] as $index => [$date, $amount, $status]) {
        PaymentTransaction::create([
            'invoice_number' => 'INV-PERF-'.$index,
            'member_id' => $member->member_id, 'subscription_id' => $subscription->subscription_id,
            'amount' => $amount, 'payment_status' => $status, 'payment_method' => 'cash',
            'payment_date' => $date, 'created_at' => now(),
        ]);
    }

    $revenueQueries = [];
    DB::listen(function ($query) use (&$revenueQueries) {
        if (str_contains(strtolower($query->sql), 'sum(') && str_contains($query->sql, 'payment_transactions')) {
            $revenueQueries[] = $query->sql;
        }
    });

    $response = $this->actingAs($user)->get(route('dashboard'));
    $response->assertOk()
        ->assertViewHas('revenueChart', fn ($chart) => $chart['values'] === [100.0, 0.0, 0.0, 0.0, 200.0, 350.0])
        ->assertViewHas('monthlyRevenue', 350.0)
        ->assertViewHas('revenueGrowth', 75.0);
    expect($revenueQueries)->toHaveCount(1);
});
