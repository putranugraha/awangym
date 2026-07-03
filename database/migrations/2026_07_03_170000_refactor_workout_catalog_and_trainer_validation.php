<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->string('exercise_code', 50)->nullable()->unique()->after('exercise_id');
            $table->string('equipment', 100)->default('none')->after('category');
        });

        Schema::table('workout_programs', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn('trainer_id');
            $table->string('program_code', 50)->nullable()->unique()->after('program_id');
            $table->string('source_name')->nullable()->after('description');
            $table->string('source_reference')->nullable()->after('source_name');
        });

        Schema::table('program_exercises', function (Blueprint $table) {
            $table->string('session_name', 100)->nullable()->after('training_day');
            $table->string('intensity', 100)->nullable()->after('rest_seconds');
        });

        Schema::table('member_programs', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->unsignedBigInteger('trainer_id')->nullable()->change();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->nullOnDelete();
        });

        Schema::create('member_exercise_checks', function (Blueprint $table) {
            $table->id('check_id');
            $table->unsignedBigInteger('member_program_id');
            $table->unsignedBigInteger('program_exercise_id');
            $table->unsignedBigInteger('validated_by');
            $table->timestamp('validated_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('member_program_id')->references('member_program_id')->on('member_programs')->cascadeOnDelete();
            $table->foreign('program_exercise_id')->references('program_exercise_id')->on('program_exercises')->cascadeOnDelete();
            $table->foreign('validated_by')->references('trainer_id')->on('personal_trainers')->restrictOnDelete();
            $table->unique(['member_program_id', 'program_exercise_id'], 'member_exercise_check_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_exercise_checks');

        Schema::table('member_programs', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->unsignedBigInteger('trainer_id')->nullable(false)->change();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->cascadeOnDelete();
        });

        Schema::table('program_exercises', function (Blueprint $table) {
            $table->dropColumn(['session_name', 'intensity']);
        });

        Schema::table('workout_programs', function (Blueprint $table) {
            $table->dropColumn(['program_code', 'source_name', 'source_reference']);
            $table->unsignedBigInteger('trainer_id');
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->cascadeOnDelete();
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['exercise_code', 'equipment']);
        });
    }
};
