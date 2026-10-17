<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_trainer_addons', function (Blueprint $table) {
            $table->id('trainer_addon_id');
            $table->unsignedBigInteger('subscription_id')->unique();
            $table->unsignedBigInteger('trainer_id');
            $table->unsignedBigInteger('created_by');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('duration_months');
            $table->unsignedInteger('session_limit');
            $table->decimal('monthly_rate', 12, 2);
            $table->timestamps();
            $table->foreign('subscription_id')->references('subscription_id')->on('membership_subscriptions')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->restrictOnDelete();
            $table->foreign('created_by')->references('user_id')->on('users')->restrictOnDelete();
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_addon_id')->nullable()->unique();
            $table->foreign('trainer_addon_id')->references('trainer_addon_id')->on('membership_trainer_addons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['trainer_addon_id']);
            $table->dropColumn('trainer_addon_id');
        });
        Schema::dropIfExists('membership_trainer_addons');
    }
};
