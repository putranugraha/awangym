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
            $table->unsignedInteger('duration_months')->nullable()->after('package_name');
        });

        DB::table('membership_packages')->update([
            'duration_months' => DB::raw('GREATEST(1, CEIL(duration_days / 30))'),
        ]);

        Schema::table('membership_packages', function (Blueprint $table) {
            $table->unsignedInteger('duration_months')->nullable(false)->change();
            $table->dropColumn('duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('membership_packages', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable()->after('package_name');
        });

        DB::table('membership_packages')->update([
            'duration_days' => DB::raw('duration_months * 30'),
        ]);

        Schema::table('membership_packages', function (Blueprint $table) {
            $table->unsignedInteger('duration_days')->nullable(false)->change();
            $table->dropColumn('duration_months');
        });
    }
};
