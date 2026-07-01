<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard', 'manage members', 'manage trainers', 'manage packages',
            'manage memberships', 'manage payments', 'view reports', 'manage exercises',
            'manage workout programs', 'assign workout programs', 'view own membership',
            'view own payments', 'view own workout program',
        ];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(compact('name'));
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $trainerRole = Role::firstOrCreate(['name' => 'personal_trainer']);
        $adminRole->syncPermissions(Permission::all());
        $memberRole->syncPermissions(['view dashboard', 'view own membership', 'view own payments', 'view own workout program']);
        $trainerRole->syncPermissions(['view dashboard', 'manage exercises', 'manage workout programs', 'assign workout programs']);

        $admin = User::create([
            'full_name' => 'Admin Awan Gym', 'email' => 'admin@awangym.test',
            'phone' => '081234567890', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $memberUser = User::create([
            'full_name' => 'Budi Santoso', 'email' => 'member@awangym.test',
            'phone' => '081234567891', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $memberUser->assignRole('member');
        $member = Member::create([
            'user_id' => $memberUser->user_id, 'member_code' => 'AGM-001', 'gender' => 'L',
            'birth_date' => '1995-05-12', 'address' => 'Alamat member', 'registered_at' => today(),
        ]);

        $trainerUser = User::create([
            'full_name' => 'Sari Trainer', 'email' => 'trainer@awangym.test',
            'phone' => '081234567892', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $trainerUser->assignRole('personal_trainer');
        PersonalTrainer::create([
            'user_id' => $trainerUser->user_id, 'trainer_code' => 'AGT-001',
            'bio' => 'Personal trainer Awan Gym.', 'employment_status' => 'active',
        ]);

        $package = MembershipPackage::create([
            'package_name' => 'Membership 3 Bulan', 'duration_days' => 90,
            'price' => 450000, 'description' => 'Akses gym selama 90 hari.',
        ]);
        $subscription = MembershipSubscription::create([
            'member_id' => $member->member_id, 'package_id' => $package->package_id,
            'created_by' => $admin->user_id, 'subscription_type' => 'new_registration',
            'start_date' => today(), 'end_date' => today()->addDays(89), 'subscription_status' => 'active',
        ]);
        PaymentTransaction::create([
            'invoice_number' => 'INV-'.now()->format('Ymd').'-001', 'member_id' => $member->member_id,
            'subscription_id' => $subscription->subscription_id, 'amount' => $package->price,
            'payment_method' => 'cash', 'payment_status' => 'paid', 'payment_date' => now(),
            'verified_by' => $admin->user_id, 'created_at' => now(),
        ]);
    }
}
