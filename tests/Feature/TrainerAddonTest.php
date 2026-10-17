<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Models\User;
use App\Services\MembershipPaymentService;
use App\Services\TrainerAddonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainerAddonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Member $member;

    private PersonalTrainer $trainer;

    private PaymentTransaction $base;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));
            DB::connection()->getPdo()->sqliteCreateFunction('CEIL', fn ($value) => ceil($value), 1);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 17)->startOfDay());
        $this->admin = $this->user('admin', ['manage payments', 'view reports']);
        $memberUser = $this->user('member', ['view own membership', 'view own payments', 'view own workout program']);
        $this->member = Member::create([
            'user_id' => $memberUser->user_id, 'member_code' => 'AGM-TEST', 'gender' => 'L',
            'birth_date' => '2000-01-01', 'address' => 'Alamat', 'registered_at' => '2026-09-17',
        ]);
        $trainerUser = $this->user('personal_trainer', ['view assigned members', 'validate member exercises']);
        $this->trainer = PersonalTrainer::create([
            'user_id' => $trainerUser->user_id, 'trainer_code' => 'AGT-TEST', 'employment_status' => 'active',
        ]);
        $package = MembershipPackage::create(['package_name' => 'Gym Mandiri 3 Bulan', 'duration_months' => 3, 'price' => 450000, 'has_trainer' => false, 'package_status' => 'active']);
        $subscription = MembershipSubscription::create([
            'member_id' => $this->member->member_id, 'package_id' => $package->package_id, 'created_by' => $this->admin->user_id,
            'subscription_type' => 'new_registration', 'start_date' => '2026-09-17', 'end_date' => '2026-12-17', 'subscription_status' => 'active',
        ]);
        $this->base = PaymentTransaction::create([
            'invoice_number' => 'INV-GYM-TEST', 'member_id' => $this->member->member_id, 'subscription_id' => $subscription->subscription_id,
            'amount' => 450000, 'payment_method' => 'cash', 'payment_status' => 'paid',
            'payment_date' => '2026-09-17 10:00:00', 'verified_by' => $this->admin->user_id, 'created_at' => '2026-09-17 10:00:00',
        ]);
    }

    public function test_only_the_two_remaining_months_are_charged_and_gym_is_unchanged(): void
    {
        $before = $this->base->fresh()->getAttributes();
        $payment = $this->createAddon(['duration_months' => 2, 'session_limit' => 24]);
        $subscription = $this->base->subscription->fresh();

        $this->assertSame('3600000.00', $payment->amount);
        $this->assertSame($before, $this->base->fresh()->getAttributes());
        $this->assertSame('2026-09-17', $subscription->start_date->toDateString());
        $this->assertSame('2026-12-17', $subscription->end_date->toDateString());
        $this->assertSame('2026-10-17', $subscription->trainer_start_date->toDateString());
        $this->assertSame('2026-12-17', $subscription->trainer_end_date->toDateString());
        $this->assertSame(24, $subscription->trainer_session_limit);
        $this->assertTrue($subscription->payment->is($this->base));
        $this->assertFalse($subscription->package->has_trainer);
        $this->assertDatabaseCount('membership_subscriptions', 1);
        $this->assertDatabaseCount('payment_transactions', 2);
        $this->assertDatabaseCount('member_programs', 0);
    }

    public function test_pending_pt_does_not_activate_trainer_and_paid_verification_is_idempotent(): void
    {
        $payment = $this->createAddon(['payment_status' => 'pending']);
        $this->assertNull($this->base->subscription->fresh()->trainer_id);
        $this->assertSame(1, MembershipSubscription::current()->count());
        $this->assertSame(0, MembershipSubscription::currentTrainer()->count());
        $service = app(MembershipPaymentService::class);
        $service->markAsPaid($payment, $this->admin->user_id);
        $paidAt = $payment->fresh()->payment_date->toDateTimeString();
        $this->travel(1)->hours();
        $service->markAsPaid($payment, $this->admin->user_id);
        $this->assertSame($paidAt, $payment->fresh()->payment_date->toDateTimeString());
        $this->assertSame(1, MembershipSubscription::currentTrainer()->count());
        $this->assertSame('2026-09-17', $this->base->subscription->fresh()->start_date->toDateString());
    }

    public function test_refund_only_deactivates_pt_and_does_not_cancel_gym(): void
    {
        $payment = $this->createAddon();
        app(MembershipPaymentService::class)->markAsUnpaid($payment, 'refunded');
        $this->assertSame('paid', $this->base->fresh()->payment_status);
        $this->assertSame(1, MembershipSubscription::current()->count());
        $this->assertSame(0, MembershipSubscription::currentTrainer()->count());
        $this->assertNull($this->base->subscription->fresh()->trainer_id);
        $this->assertSame('refunded', $payment->fresh()->payment_status);
        $this->assertNull($payment->fresh()->payment_date);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_addon_requests_leave_no_partial_records(array $input): void
    {
        try {
            $this->createAddon($input);
            $this->fail('Expected invalid addon to be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('membership_trainer_addons', 0);
            $this->assertDatabaseCount('payment_transactions', 1);
            $this->assertSame('paid', $this->base->fresh()->payment_status);
        }
    }

    public static function invalidInputs(): array
    {
        return [
            'three months instead of two' => [['duration_months' => 3]],
            'past start' => [['start_date' => '2026-10-16']],
            'not a real date' => [['start_date' => '2026-02-31']],
            'fractional month' => [['duration_months' => 1.5]],
            'too many sessions' => [['session_limit' => 13]],
            'no sessions' => [['session_limit' => 0]],
            'less than a month remaining' => [['start_date' => '2026-12-01']],
        ];
    }

    public function test_duplicate_addon_is_rejected(): void
    {
        $this->createAddon(['payment_status' => 'pending']);
        $this->expectException(ValidationException::class);
        $this->createAddon();
    }

    public function test_existing_bundled_trainer_cannot_be_bought_again(): void
    {
        $this->base->subscription->update(['trainer_id' => $this->trainer->trainer_id, 'trainer_session_limit' => 36]);
        $this->expectException(ValidationException::class);
        $this->createAddon();
    }

    public function test_unpaid_gym_does_not_qualify_even_if_an_addon_is_paid(): void
    {
        $payment = $this->createAddon();
        app(MembershipPaymentService::class)->markAsUnpaid($this->base, 'refunded');
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame(0, MembershipSubscription::current()->count());
        $this->assertSame(0, MembershipSubscription::currentTrainer()->count());
        app(MembershipPaymentService::class)->markAsPaid($this->base, $this->admin->user_id);
        $this->assertSame('2026-09-17', $this->base->subscription->fresh()->start_date->toDateString());
    }

    public function test_inactive_trainer_cannot_be_assigned_or_verified(): void
    {
        $payment = $this->createAddon(['payment_status' => 'pending']);
        $this->trainer->user->update(['account_status' => 'inactive']);
        $this->expectException(ValidationException::class);
        app(MembershipPaymentService::class)->markAsPaid($payment, $this->admin->user_id);
    }

    public function test_trainer_access_follows_the_pt_period_instead_of_the_three_month_gym_period(): void
    {
        $this->createAddon(['start_date' => '2026-10-20']);
        $this->assertSame(0, MembershipSubscription::currentTrainer()->count());
        $this->travelTo(now()->setDate(2026, 10, 20));
        $this->assertSame(1, MembershipSubscription::currentTrainer()->count());
        $this->travelTo(now()->setDate(2026, 11, 21));
        $this->assertSame(0, MembershipSubscription::currentTrainer()->count());
        $this->assertSame(1, MembershipSubscription::current()->count());
    }

    public function test_session_dates_and_quota_are_enforced_for_the_addon(): void
    {
        $this->createAddon(['session_limit' => 1]);
        $this->actingAs($this->trainer->user);
        $component = Livewire::test('app::trainer-members.show', ['subscription' => $this->base->subscription->fresh()]);
        $component->set('notes', 'Latihan bersama trainer')->set('session_date', '2026-10-16')->call('saveSession')->assertHasErrors('session_date');
        $component->set('session_date', '2026-10-17')->call('saveSession')->assertHasNoErrors();
        $this->travelTo(now()->setDate(2026, 10, 18));
        $component->set('notes', 'Sesi berikutnya')->set('session_date', '2026-10-18')->call('saveSession')->assertHasErrors('session_date');
        $this->travelTo(now()->setDate(2026, 11, 18));
        $component->set('session_date', '2026-11-18')->call('saveSession')->assertHasErrors('session_date');
        $this->assertDatabaseCount('trainer_sessions', 1);
    }

    public function test_deleting_pending_addon_never_deletes_gym_or_its_payment(): void
    {
        $payment = $this->createAddon(['payment_status' => 'pending']);
        $this->actingAs($this->admin);
        Livewire::test('app::transactions.index')->set('deletingId', $payment->transaction_id)->call('deleteTransaction')->assertHasNoErrors();
        $this->assertDatabaseCount('membership_trainer_addons', 0);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertDatabaseHas('membership_subscriptions', ['subscription_id' => $this->base->subscription_id]);
        $this->assertSame('paid', $this->base->fresh()->payment_status);
    }

    public function test_base_invoice_with_addon_cannot_be_deleted(): void
    {
        $this->createAddon(['payment_status' => 'pending']);
        app(MembershipPaymentService::class)->markAsUnpaid($this->base, 'pending');
        $this->expectException(ValidationException::class);
        app(MembershipPaymentService::class)->deleteUnpaid($this->base);
    }

    public function test_admin_can_create_and_verify_addon_from_transaction_components(): void
    {
        $this->actingAs($this->admin)->get(route('transactions.edit', $this->base))->assertOk()->assertSee('Buat Tagihan Tambahan PT');
        Livewire::test('app::transactions.trainer-addon', ['transaction' => $this->base])
            ->set('trainer_id', (string) $this->trainer->trainer_id)->set('duration_months', 2)
            ->set('start_date', '2026-10-17')->set('payment_status', 'pending')
            ->call('save')->assertHasNoErrors();
        $payment = PaymentTransaction::whereNotNull('trainer_addon_id')->sole();
        $this->assertSame('3600000.00', $payment->amount);
        $this->get(route('transactions.edit', $payment))->assertOk()->assertSee('Tambahan Personal Trainer')->assertSee('INV-GYM-TEST');
        Livewire::test('app::transactions.edit', ['transaction' => $payment])->set('payment_status', 'paid')->call('save')->assertHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->get(route('transactions.edit', $this->base))->assertOk()->assertSee('Buka Tagihan PT')->assertDontSee('Buat Tagihan Tambahan PT');
        Livewire::test('app::reports.index')->assertSet('month', '2026-10')->assertSee('3.600.000');
    }

    public function test_member_sees_extra_payment_and_pt_dates_but_cannot_create_addons(): void
    {
        $payment = $this->createAddon();
        $this->actingAs($this->member->user);
        $this->get(route('payments.index'))->assertOk()->assertSee($payment->invoice_number)->assertSee('Tambahan PT');
        $this->get(route('membership.show'))->assertOk()->assertSee('Tambahan PT');
        $this->get(route('profile.show'))->assertOk()->assertSee('Tambahan PT');
        $this->get(route('transactions.edit', $this->base))->assertForbidden();
        Livewire::test('app::transactions.trainer-addon', ['transaction' => $this->base])->assertForbidden();
    }

    private function createAddon(array $input = []): PaymentTransaction
    {
        return app(TrainerAddonService::class)->create($this->base, array_merge([
            'trainer_id' => $this->trainer->trainer_id, 'start_date' => '2026-10-17',
            'duration_months' => 1, 'session_limit' => 12, 'payment_method' => 'transfer', 'payment_status' => 'paid',
        ], $input), $this->admin->user_id);
    }

    private function user(string $role, array $permissions): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }
}
