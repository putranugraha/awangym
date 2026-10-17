<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\MembershipTrainerAddon;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Models\User;
use App\Services\TrainerSalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerSalaryTest extends TestCase
{
    use RefreshDatabase;

    private PersonalTrainer $trainer;

    protected function beforeRefreshingDatabase(): void
    {
        // Compatibility for the existing MySQL duration migration in SQLite tests.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));
            DB::connection()->getPdo()->sqliteCreateFunction('CEIL', fn ($value) => ceil($value), 1);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 17)->startOfDay());
        $this->trainer = $this->trainer('Trainer Salary Saya');
    }

    public function test_three_prepaid_months_are_allocated_one_million_per_full_month(): void
    {
        $this->subscription('2026-10-01', '2027-01-01');
        foreach (['2026-10', '2026-11', '2026-12'] as $month) {
            $summary = $this->summary($month);
            $this->assertSame(1000000, $summary['total']);
            $this->assertSame(1, $summary['member_count']);
        }
        $this->assertSame(0, $this->summary('2027-01')['total']);
    }

    public function test_a_month_crossing_two_calendar_months_is_split_without_overpayment(): void
    {
        $this->subscription('2026-10-20', '2026-11-20');
        $october = $this->summary('2026-10');
        $november = $this->summary('2026-11');
        $this->assertSame(387097, $october['total']);
        $this->assertSame(612903, $november['total']);
        $this->assertSame(1000000, $october['total'] + $november['total']);
        $this->assertSame(12, $october['members'][0]['days']);
        $this->assertSame(19, $november['members'][0]['days']);
        $this->assertSame('2026-11-19', $november['members'][0]['segments'][0]['counted_until']->toDateString());
    }

    public function test_three_mid_month_cycles_still_total_exactly_three_million(): void
    {
        $this->subscription('2026-09-20', '2026-12-20');
        $total = 0;
        foreach (['2026-09', '2026-10', '2026-11', '2026-12'] as $month) {
            $amount = $this->summary($month)['total'];
            $this->assertLessThan(2000000, $amount);
            $total += $amount;
        }
        $this->assertSame(3000000, $total);
    }

    public function test_addon_salary_only_counts_its_pt_period_and_requires_both_payments(): void
    {
        $subscription = $this->subscription('2026-09-01', '2026-12-01');
        $addon = $this->addon($subscription, '2026-10-20', '2026-11-20');
        $this->assertSame(0, $this->summary('2026-09')['total']);
        $this->assertSame(387097, $this->summary('2026-10')['total']);
        $this->assertSame('Tambahan PT', $this->summary('2026-10')['members'][0]['segments'][0]['source']);
        $addon->payment->update(['payment_status' => 'pending']);
        $this->assertSame(0, $this->summary('2026-10')['total']);
        $addon->payment->update(['payment_status' => 'paid']);
        $subscription->payment->update(['payment_status' => 'pending']);
        $this->assertSame(0, $this->summary('2026-10')['total']);
    }

    #[DataProvider('unpaidStatuses')]
    public function test_unpaid_or_refunded_pt_is_not_included(string $status): void
    {
        $subscription = $this->subscription('2026-10-01', '2026-11-01');
        $subscription->payment->update(['payment_status' => $status]);
        $this->assertSame(0, $this->summary('2026-10')['total']);
    }

    public static function unpaidStatuses(): array
    {
        return [['pending'], ['failed'], ['refunded']];
    }

    public function test_cancelled_memberships_and_members_without_pt_are_excluded(): void
    {
        $cancelled = $this->subscription('2026-10-01', '2026-11-01');
        $cancelled->update(['subscription_status' => 'cancelled']);
        $gymOnly = $this->subscription('2026-10-01', '2026-11-01');
        $gymOnly->update(['trainer_id' => null]);
        $this->assertSame(0, $this->summary('2026-10')['member_count']);
    }

    public function test_historical_salary_does_not_depend_on_membership_still_being_active_today(): void
    {
        $subscription = $this->subscription('2026-08-01', '2026-09-01');
        $subscription->update(['subscription_status' => 'expired']);
        $this->assertSame(1000000, $this->summary('2026-08')['total']);
        $this->assertSame(0, $this->summary('2026-10')['total']);
    }

    public function test_late_payment_only_counts_days_after_settlement(): void
    {
        $subscription = $this->subscription('2026-10-01', '2026-11-01');
        $subscription->payment->update(['payment_date' => '2026-10-20 10:00:00']);
        $summary = $this->summary('2026-10');
        $this->assertSame(12, $summary['service_days']);
        $this->assertSame('2026-10-20', $summary['members'][0]['segments'][0]['counted_from']->toDateString());
        $this->assertSame(387097, $summary['total']);
        $subscription->payment->update(['payment_date' => '2026-11-01 10:00:00']);
        $this->assertSame(0, $this->summary('2026-10')['total']);
    }

    public function test_overlapping_transactions_do_not_count_the_same_members_day_twice(): void
    {
        $member = $this->member('Member Ganda');
        $this->subscription('2026-10-01', '2026-11-01', $member);
        $this->subscription('2026-10-01', '2026-11-01', $member);
        $summary = $this->summary('2026-10');
        $this->assertSame(1000000, $summary['total']);
        $this->assertSame(1, $summary['member_count']);
        $this->assertSame(31, $summary['service_days']);
        $this->assertSame(31, $summary['duplicate_days']);
    }

    public function test_successive_periods_for_the_same_member_are_grouped_into_one_row(): void
    {
        $member = $this->member('Member Lanjutan');
        $this->subscription('2026-09-20', '2026-10-20', $member);
        $this->subscription('2026-10-20', '2026-11-20', $member);
        $summary = $this->summary('2026-10');
        $this->assertSame(1, $summary['member_count']);
        $this->assertCount(2, $summary['members'][0]['segments']);
        $this->assertSame(31, $summary['service_days']);
        $this->assertSame(0, $summary['duplicate_days']);
    }

    public function test_month_end_and_leap_year_cycles_preserve_the_total_budget(): void
    {
        $this->subscription('2028-01-31', '2028-03-31');
        $total = $this->summary('2028-01')['total'] + $this->summary('2028-02')['total'] + $this->summary('2028-03')['total'];
        $this->assertSame(2000000, $total);
        $this->assertSame(29, $this->summary('2028-02')['service_days']);
    }

    public function test_multiple_members_increase_salary_and_other_trainers_are_never_included(): void
    {
        $this->subscription('2026-10-01', '2026-11-01', $this->member('Member Saya A'));
        $this->subscription('2026-10-01', '2026-11-01', $this->member('Member Saya B'));
        $otherTrainer = $this->trainer('Trainer Lain');
        $other = $this->subscription('2026-10-01', '2026-11-01', $this->member('Member Rahasia'));
        $other->update(['trainer_id' => $otherTrainer->trainer_id]);
        $this->assertSame(2000000, $this->summary('2026-10')['total']);
        $this->actingAs($this->trainer->user)->get(route('salary.index', ['month' => '2026-10', 'trainer_id' => $otherTrainer->trainer_id]))
            ->assertOk()->assertSee('Member Saya A')->assertSee('Member Saya B')->assertDontSee('Member Rahasia')
            ->assertSee('Rp 2.000.000')->assertSee('ESTIMASI')->assertSee('Salary');
    }

    public function test_salary_requires_trainer_role_and_specific_permission(): void
    {
        $this->get(route('salary.index'))->assertRedirect(route('login'));
        foreach (['member', 'admin'] as $role) {
            $user = User::factory()->create();
            $user->assignRole(Role::findOrCreate($role, 'web'));
            $user->givePermissionTo(Permission::findOrCreate('view own salary', 'web'));
            $this->actingAs($user)->get(route('salary.index'))->assertForbidden();
            Livewire::test('app::salary.index')->assertForbidden();
        }
        $this->trainer->user->revokePermissionTo('view own salary');
        $this->actingAs($this->trainer->user)->get(route('salary.index'))->assertForbidden();
    }

    public function test_month_filter_empty_state_and_missing_trainer_profile(): void
    {
        $this->subscription('2026-10-01', '2026-11-01');
        $this->actingAs($this->trainer->user);
        Livewire::test('app::salary.index')->assertSet('month', '2026-10')->assertSee('Rp 1.000.000')
            ->set('month', '2026-11')->assertHasNoErrors()->assertSee('Belum ada gaji yang terhitung')
            ->set('month', 'not-a-month')->assertHasErrors('month');
        $this->get(route('salary.index', ['month' => '2026-99']))->assertOk()->assertSee('Oktober 2026');
        $user = User::factory()->create();
        $user->assignRole('personal_trainer');
        $user->givePermissionTo('view own salary');
        $this->actingAs($user)->get(route('salary.index'))->assertOk()->assertSee('Profil trainer belum lengkap');
    }

    public function test_permission_migration_grants_salary_to_existing_trainer_role_without_resetting_permissions(): void
    {
        $role = Role::findByName('personal_trainer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('view assigned members', 'web'));
        $migration = require database_path('migrations/2026_10_17_130000_add_trainer_salary_permission.php');
        $migration->up();
        $migration->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('view own salary'));
        $this->assertTrue($role->fresh()->hasPermissionTo('view assigned members'));
    }

    private function summary(string $month): array
    {
        return app(TrainerSalaryService::class)->summarize($this->trainer, $month);
    }

    private function trainer(string $name): PersonalTrainer
    {
        $user = User::factory()->create(['full_name' => $name]);
        $user->assignRole(Role::findOrCreate('personal_trainer', 'web'));
        $user->givePermissionTo(Permission::findOrCreate('view own salary', 'web'));

        return PersonalTrainer::create(['user_id' => $user->user_id, 'trainer_code' => 'AGT-'.$user->user_id, 'employment_status' => 'active']);
    }

    private function member(string $name): Member
    {
        $user = User::factory()->create(['full_name' => $name]);

        return Member::create(['user_id' => $user->user_id, 'member_code' => 'AGM-'.$user->user_id,
            'gender' => 'L', 'birth_date' => '2000-01-01', 'address' => 'Alamat', 'registered_at' => today()]);
    }

    private function subscription(string $start, string $end, ?Member $member = null): MembershipSubscription
    {
        $member ??= $this->member('Member Salary');
        $package = MembershipPackage::create(['package_name' => 'Membership dengan PT', 'duration_months' => 3,
            'price' => 5850000, 'has_trainer' => true, 'trainer_session_limit' => 36, 'package_status' => 'active']);
        $subscription = MembershipSubscription::create([
            'member_id' => $member->member_id, 'package_id' => $package->package_id,
            'trainer_id' => $this->trainer->trainer_id, 'trainer_session_limit' => 36, 'created_by' => $this->trainer->user_id,
            'subscription_type' => 'new_registration', 'start_date' => $start, 'end_date' => $end, 'subscription_status' => 'active',
        ]);
        PaymentTransaction::create([
            'invoice_number' => 'INV-SALARY-'.$subscription->subscription_id, 'member_id' => $member->member_id,
            'subscription_id' => $subscription->subscription_id, 'amount' => $package->price,
            'payment_method' => 'cash', 'payment_status' => 'paid', 'payment_date' => $start.' 09:00:00', 'created_at' => $start.' 09:00:00',
        ]);

        return $subscription;
    }

    private function addon(MembershipSubscription $subscription, string $start, string $end): MembershipTrainerAddon
    {
        $addon = MembershipTrainerAddon::create([
            'subscription_id' => $subscription->subscription_id, 'trainer_id' => $this->trainer->trainer_id,
            'created_by' => $this->trainer->user_id, 'start_date' => $start, 'end_date' => $end,
            'duration_months' => 1, 'session_limit' => 12, 'monthly_rate' => 1800000,
        ]);
        PaymentTransaction::create([
            'invoice_number' => 'INV-SALARY-PT-'.$addon->trainer_addon_id, 'member_id' => $subscription->member_id,
            'subscription_id' => $subscription->subscription_id, 'trainer_addon_id' => $addon->trainer_addon_id,
            'amount' => 1800000, 'payment_method' => 'cash', 'payment_status' => 'paid', 'payment_date' => $start.' 09:00:00', 'created_at' => $start.' 09:00:00',
        ]);

        return $addon;
    }
}
