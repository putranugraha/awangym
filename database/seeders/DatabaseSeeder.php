<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberProgram;
use App\Models\MembershipPackage;
use App\Models\MembershipSubscription;
use App\Models\PaymentTransaction;
use App\Models\PersonalTrainer;
use App\Models\User;
use App\Models\WorkoutProgram;
use App\Support\AccessControl;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = AccessControl::CORE_PERMISSIONS;
        foreach ($permissions as $name) {
            Permission::firstOrCreate(compact('name'));
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $trainerRole = Role::firstOrCreate(['name' => 'personal_trainer']);
        $adminRole->syncPermissions([
            'view dashboard',
            'manage users',
            'manage roles and permissions',
            'manage members',
            'manage trainers',
            'manage packages',
            'manage memberships',
            'manage payments',
            'view reports',
            'view workout catalog',
            'assign workout programs',
        ]);
        $memberRole->syncPermissions(['view dashboard', 'view own membership', 'view own payments', 'view own workout program']);
        $trainerRole->syncPermissions(['view dashboard', 'view workout catalog', 'view assigned members', 'validate member exercises']);
        Permission::whereIn('name', ['manage exercises', 'manage workout programs'])->delete();

        $admin = User::updateOrCreate(['email' => 'admin@awangym.test'], [
            'full_name' => 'Admin Awan Gym', 'email' => 'admin@awangym.test',
            'phone' => '081234567890', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $memberUser = User::updateOrCreate(['email' => 'member@awangym.test'], [
            'full_name' => 'Budi Santoso', 'email' => 'member@awangym.test',
            'phone' => '081234567891', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $memberUser->assignRole('member');
        $member = Member::updateOrCreate(['user_id' => $memberUser->user_id], [
            'user_id' => $memberUser->user_id, 'member_code' => 'AGM-001', 'gender' => 'L',
            'birth_date' => '1995-05-12', 'address' => 'Alamat member', 'registered_at' => today(),
        ]);

        $trainerUser = User::updateOrCreate(['email' => 'trainer@awangym.test'], [
            'full_name' => 'Sari Trainer', 'email' => 'trainer@awangym.test',
            'phone' => '081234567892', 'password' => 'password', 'email_verified_at' => now(),
        ]);
        $trainerUser->assignRole('personal_trainer');
        $trainer = PersonalTrainer::updateOrCreate(['user_id' => $trainerUser->user_id], [
            'user_id' => $trainerUser->user_id, 'trainer_code' => 'AGT-001',
            'bio' => 'Personal trainer Awan Gym.', 'employment_status' => 'active',
        ]);

        $package = MembershipPackage::updateOrCreate(['package_name' => 'Membership 3 Bulan'], [
            'package_name' => 'Membership 3 Bulan', 'duration_months' => 3,
            'price' => 450000, 'description' => 'Akses gym selama 90 hari.',
        ]);
        $subscription = MembershipSubscription::firstOrCreate([
            'member_id' => $member->member_id,
            'subscription_type' => 'new_registration',
        ], [
            'member_id' => $member->member_id, 'package_id' => $package->package_id,
            'created_by' => $admin->user_id, 'subscription_type' => 'new_registration',
            'start_date' => today(), 'end_date' => today()->addDays(89), 'subscription_status' => 'active',
        ]);
        PaymentTransaction::updateOrCreate(['invoice_number' => 'INV-SEED-001'], [
            'member_id' => $member->member_id,
            'subscription_id' => $subscription->subscription_id, 'amount' => $package->price,
            'payment_method' => 'cash', 'payment_status' => 'paid', 'payment_date' => now(),
            'verified_by' => $admin->user_id, 'created_at' => now(),
        ]);

        $this->call([
            ExerciseCatalogSeeder::class,
            WorkoutProgramSeeder::class,
        ]);

        $beginnerProgram = WorkoutProgram::where('program_code', 'GYM-BEG-001')->firstOrFail();
        MemberProgram::firstOrCreate(
            ['member_id' => $member->member_id, 'program_id' => $beginnerProgram->program_id],
            [
                'trainer_id' => $trainer->trainer_id,
                'assigned_date' => today(),
                'start_date' => today(),
                'end_date' => today()->addWeeks($beginnerProgram->duration_weeks)->subDay(),
                'progress_percentage' => 0,
                'program_status' => 'active',
                'trainer_notes' => 'Program contoh dengan pendampingan PT opsional.',
            ]
        );
    }
}
