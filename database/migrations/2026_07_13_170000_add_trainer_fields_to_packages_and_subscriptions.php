<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_packages', function (Blueprint $table) {
            $table->boolean('has_trainer')->default(false)->after('price');
        });

        Schema::table('membership_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_id')->nullable()->after('package_id');
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn('trainer_id');
        });

        Schema::table('membership_packages', function (Blueprint $table) {
            $table->dropColumn('has_trainer');
        });
    }
};
