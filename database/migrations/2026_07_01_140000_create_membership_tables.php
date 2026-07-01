<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id('member_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('member_code', 20)->unique();
            $table->enum('gender', ['L', 'P']);
            $table->date('birth_date');
            $table->text('address');
            $table->string('profile_photo')->nullable();
            $table->date('registered_at');
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });

        Schema::create('personal_trainers', function (Blueprint $table) {
            $table->id('trainer_id');
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('trainer_code', 20)->unique();
            $table->string('profile_photo')->nullable();
            $table->text('bio')->nullable();
            $table->enum('employment_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });

        Schema::create('membership_packages', function (Blueprint $table) {
            $table->id('package_id');
            $table->string('package_name', 100);
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->enum('package_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('membership_subscriptions', function (Blueprint $table) {
            $table->id('subscription_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('created_by');
            $table->enum('subscription_type', ['new_registration', 'renewal']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('subscription_status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members')->cascadeOnDelete();
            $table->foreign('package_id')->references('package_id')->on('membership_packages')->restrictOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
            $table->index(['member_id', 'subscription_status', 'end_date'], 'subscriptions_member_status_end_idx');
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->string('invoice_number', 30)->unique();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('subscription_id');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'e_wallet']);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('subscription_id')->on('membership_subscriptions')->cascadeOnDelete();
            $table->foreign('verified_by')->references('user_id')->on('users')->nullOnDelete();
            $table->index(['payment_status', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('membership_subscriptions');
        Schema::dropIfExists('membership_packages');
        Schema::dropIfExists('personal_trainers');
        Schema::dropIfExists('members');
    }
};
