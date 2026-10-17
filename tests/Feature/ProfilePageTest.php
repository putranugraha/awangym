<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Models\TrainerSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        // The existing duration migration uses MySQL functions. Keep this
        // compatibility shim local to these tests without changing that migration.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$values) => max($values));
            DB::connection()->getPdo()->sqliteCreateFunction('CEIL', fn ($value) => ceil($value), 1);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 17)->startOfDay());
    }

    public function test_guests_must_log_in(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_member_sees_own_current_package_trainer_and_upcoming_renewal(): void
    {
        $member = $this->member();
        $trainer = $this->trainer('Pelatih Pendamping');
        $current = $this->subscription($member, ['trainer_id' => $trainer->trainer_id, 'trainer_session_limit' => 12]);
        TrainerSession::create([
            'subscription_id' => $current->subscription_id,
            'trainer_id' => $trainer->trainer_id,
            'session_date' => today(),
            'notes' => 'Latihan pertama',
        ]);
        $upcoming = $this->subscription($member, ['start_date' => today()->addMonth(), 'end_date' => today()->addMonths(2)]);
        $other = $this->member();
        $other->user->update(['full_name' => 'Identitas Member Lain', 'email' => 'private@example.test']);
        $this->subscription($other);

        $this->actingAs($member->user)->get(route('profile.show', ['user' => $other->user_id]))
            ->assertOk()
            ->assertSee($member->member_code)
            ->assertSee('Pelatih Pendamping')
            ->assertSee('Perpanjangan sudah siap')
            ->assertSee('Detail Membership')
            ->assertSee('Riwayat pembayaran')
            ->assertDontSee('Identitas Member Lain')
            ->assertDontSee('private@example.test')
            ->assertViewHas('subscription', fn ($item) => $item->is($current) && $item->trainer_sessions_count === 1)
            ->assertViewHas('upcomingSubscription', fn ($item) => $item->is($upcoming))
            ->assertViewHas('membershipStatus', fn ($status) => $status['key'] === 'active');
    }

    #[DataProvider('membershipStates')]
    public function test_membership_states_are_distinguished(string $payment, string $subscriptionStatus, int $startOffset, int $endOffset, string $expectedKey, string $expectedLabel): void
    {
        $member = $this->member();
        $this->subscription($member, [
            'start_date' => today()->addDays($startOffset),
            'end_date' => today()->addDays($endOffset),
            'subscription_status' => $subscriptionStatus,
        ], $payment);

        $this->actingAs($member->user)->get(route('profile.show'))
            ->assertOk()
            ->assertSee($expectedLabel)
            ->assertSee('Tanpa personal trainer')
            ->assertViewHas('membershipStatus', fn ($status) => $status['key'] === $expectedKey);
    }

    public static function membershipStates(): array
    {
        return [
            'expired' => ['paid', 'active', -40, -10, 'expired', 'Kedaluwarsa'],
            'pending' => ['pending', 'active', -1, 29, 'pending', 'Menunggu pembayaran'],
            'future' => ['paid', 'active', 10, 40, 'upcoming', 'Belum dimulai'],
            'cancelled' => ['paid', 'cancelled', -1, 29, 'cancelled', 'Dibatalkan'],
            'failed' => ['failed', 'active', -1, 29, 'failed', 'Pembayaran gagal'],
            'refunded' => ['refunded', 'active', -1, 29, 'refunded', 'Pembayaran dikembalikan'],
            'last day' => ['paid', 'active', -30, 0, 'expiring', 'Masa aktif berakhir hari ini.'],
        ];
    }

    public function test_pending_renewal_does_not_replace_the_current_paid_package(): void
    {
        $member = $this->member();
        $current = $this->subscription($member);
        $this->subscription($member, ['start_date' => today()->addMonth(), 'end_date' => today()->addMonths(2)], 'pending');

        $this->actingAs($member->user)->get(route('profile.show'))
            ->assertOk()
            ->assertViewHas('subscription', fn ($item) => $item->is($current))
            ->assertSee('Ada 1 transaksi yang masih menunggu pembayaran');
    }

    public function test_member_without_a_package_sees_an_empty_state(): void
    {
        $member = $this->member();

        $this->actingAs($member->user)->get(route('profile.show'))
            ->assertOk()->assertSee('Belum berlangganan')->assertSee('Belum ada pendamping');
    }

    public function test_trainer_statistics_only_include_their_own_assignments(): void
    {
        $trainer = $this->trainer('Trainer Saya');
        $member = $this->member();
        $current = $this->subscription($member, ['trainer_id' => $trainer->trainer_id, 'trainer_session_limit' => 12]);
        TrainerSession::create([
            'subscription_id' => $current->subscription_id, 'trainer_id' => $trainer->trainer_id,
            'session_date' => today(), 'notes' => 'Sesi aktif',
        ]);
        $expired = $this->subscription($member, [
            'trainer_id' => $trainer->trainer_id, 'trainer_session_limit' => 12,
            'start_date' => today()->subMonths(2), 'end_date' => today()->subMonth(),
        ]);
        TrainerSession::create([
            'subscription_id' => $expired->subscription_id, 'trainer_id' => $trainer->trainer_id,
            'session_date' => today()->subMonth(), 'notes' => 'Sesi lama',
        ]);
        $this->subscription($this->member(), ['trainer_id' => $trainer->trainer_id, 'trainer_session_limit' => 12], 'pending');
        $otherTrainer = $this->trainer('Trainer Lain Rahasia');
        $this->subscription($this->member(), ['trainer_id' => $otherTrainer->trainer_id, 'trainer_session_limit' => 24]);

        $this->actingAs($trainer->user)->get(route('profile.show'))
            ->assertOk()->assertSee('Trainer Saya')->assertSee('Lihat Member Binaan')
            ->assertDontSee('Trainer Lain Rahasia')
            ->assertViewHas('trainerStats', ['members' => 1, 'sessions' => 2, 'remaining' => 11]);
    }

    public function test_admin_sees_their_actual_permissions_and_settings_links(): void
    {
        $admin = $this->userWithRole('admin', ['manage members']);

        $this->actingAs($admin)->get(route('profile.show'))
            ->assertOk()->assertSee('Hak Akses Saya')->assertSee('Mengelola member')
            ->assertDontSee('Mengelola pembayaran')
            ->assertSee(route('profile.edit'))->assertSee(route('security.edit'));
    }

    public function test_missing_role_profiles_do_not_crash_the_page(): void
    {
        foreach (['member', 'personal_trainer'] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee('belum lengkap');
        }

        $this->actingAs(User::factory()->create())->get(route('profile.show'))->assertOk()->assertSee('Pengguna');
    }

    public function test_user_manager_can_open_the_selected_members_profile(): void
    {
        $admin = $this->userWithRole('admin', ['manage users', 'view own membership', 'view own payments']);
        $member = $this->member();
        $trainer = $this->trainer('Trainer Member Terpilih');
        $subscription = $this->subscription($member, ['trainer_id' => $trainer->trainer_id, 'trainer_session_limit' => 12]);

        $this->actingAs($admin)->get(route('users.profile', $member->user))
            ->assertOk()->assertSee('Profil Pengguna')->assertSee('Kembali ke User')
            ->assertSee($member->member_code)->assertSee('Trainer Member Terpilih')
            ->assertSee(route('users.edit', $member->user))
            ->assertDontSee('Keamanan akun')->assertDontSee('Detail Membership')->assertDontSee('Riwayat pembayaran')
            ->assertViewHas('user', fn ($user) => $user->is($member->user))
            ->assertViewHas('subscription', fn ($item) => $item->is($subscription))
            ->assertViewHas('isOwnProfile', false);

        $this->get(route('profile.show'))->assertOk()
            ->assertViewHas('user', fn ($user) => $user->is($admin))
            ->assertViewHas('isOwnProfile', true);
    }

    public function test_user_manager_can_view_trainer_and_admin_profiles(): void
    {
        $viewer = $this->userWithRole('admin', ['manage users']);
        $trainer = $this->trainer('Trainer Terpilih');
        $admin = $this->userWithRole('admin', ['manage packages']);

        $this->actingAs($viewer)->get(route('users.profile', $trainer->user))
            ->assertOk()->assertSee('Trainer Terpilih')->assertSee($trainer->trainer_code)
            ->assertSee('Aktivitas Trainer')->assertDontSee('Lihat Member Binaan')
            ->assertViewHas('trainerStats', ['members' => 0, 'sessions' => 0, 'remaining' => 0]);
        $this->get(route('users.profile', $admin))
            ->assertOk()->assertSee('Hak Akses Pengguna')->assertSee('Mengelola paket')
            ->assertViewHas('user', fn ($user) => $user->is($admin));
    }

    public function test_other_profiles_require_manage_users_permission(): void
    {
        $target = $this->member();
        $url = route('users.profile', $target->user);
        $this->get($url)->assertRedirect(route('login'));

        foreach (['member', 'personal_trainer', 'admin'] as $role) {
            $this->actingAs($this->userWithRole($role))->get($url)->assertForbidden();
        }
    }

    public function test_unknown_user_profile_returns_not_found(): void
    {
        $this->actingAs($this->userWithRole('admin', ['manage users']))
            ->get(route('users.profile', 999999))->assertNotFound();
    }

    public function test_users_table_links_to_each_users_profile_and_preserves_edit_actions(): void
    {
        $admin = $this->userWithRole('admin', ['manage users']);
        $member = $this->member();

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()->assertSee(route('users.profile', $member->user))
            ->assertSee(route('users.profile', $admin))
            ->assertSee(route('users.edit', $member->user))
            ->assertSee('Lihat Profil')->assertSee('Hapus User');
    }

    private function userWithRole(string $role, array $permissions = []): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        return $user;
    }

    private function member(): Member
    {
        $user = $this->userWithRole('member', ['view own membership', 'view own payments', 'view own workout program']);

        return Member::create([
            'user_id' => $user->user_id, 'member_code' => 'AGM-'.$user->user_id,
            'gender' => 'L', 'birth_date' => '2000-01-01', 'address' => 'Alamat member', 'registered_at' => today(),
        ]);
    }

    private function trainer(string $name): PersonalTrainer
    {
        $user = $this->userWithRole('personal_trainer', ['view assigned members']);
        $user->update(['full_name' => $name]);

        return PersonalTrainer::create([
            'user_id' => $user->user_id, 'trainer_code' => 'AGT-'.$user->user_id,
            'employment_status' => 'active', 'bio' => 'Pendamping latihan member.',
        ]);
    }

    private function subscription(Member $member, array $attributes = [], string $payment = 'paid'): MembershipSubscription
    {
        $package = MembershipPackage::create([
            'package_name' => 'Paket '.$member->member_code, 'duration_months' => 1,
            'price' => 150000, 'package_status' => 'active',
        ]);
        $subscription = MembershipSubscription::create(array_merge([
            'member_id' => $member->member_id, 'package_id' => $package->package_id,
            'created_by' => $member->user_id, 'subscription_type' => 'new_registration',
            'start_date' => today()->subDay(), 'end_date' => today()->addDays(29), 'subscription_status' => 'active',
        ], $attributes));
        PaymentTransaction::create([
            'member_id' => $member->member_id, 'subscription_id' => $subscription->subscription_id,
            'invoice_number' => 'INV-PROFILE-'.$subscription->subscription_id,
            'amount' => $package->price, 'payment_method' => 'cash', 'payment_status' => $payment,
            'payment_date' => $payment === 'paid' ? now() : null, 'created_at' => now(),
        ]);

        return $subscription;
    }
}
