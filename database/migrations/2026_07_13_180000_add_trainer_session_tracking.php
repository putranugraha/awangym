<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_packages', function (Blueprint $table) {
            $table->unsignedInteger('trainer_session_limit')->nullable()->after('has_trainer');
        });

        Schema::table('membership_subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('trainer_session_limit')->nullable()->after('trainer_id');
        });

        DB::table('membership_packages')
            ->where('has_trainer', true)
            ->update(['trainer_session_limit' => DB::raw('duration_months * 12')]);

        DB::table('membership_packages')->where('has_trainer', true)->orderBy('package_id')->each(function (object $package) {
            DB::table('membership_subscriptions')
                ->where('package_id', $package->package_id)
                ->whereNotNull('trainer_id')
                ->update(['trainer_session_limit' => $package->trainer_session_limit]);
        });

        Schema::create('trainer_sessions', function (Blueprint $table) {
            $table->id('trainer_session_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('trainer_id');
            $table->date('session_date');
            $table->text('notes');
            $table->timestamps();

            $table->foreign('subscription_id')->references('subscription_id')->on('membership_subscriptions')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->cascadeOnDelete();
            $table->unique(['subscription_id', 'session_date']);
            $table->index(['trainer_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_sessions');

        Schema::table('membership_subscriptions', function (Blueprint $table) {
            $table->dropColumn('trainer_session_limit');
        });

        Schema::table('membership_packages', function (Blueprint $table) {
            $table->dropColumn('trainer_session_limit');
        });
    }
};
