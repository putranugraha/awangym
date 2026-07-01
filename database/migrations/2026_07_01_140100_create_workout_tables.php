<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id('exercise_id');
            $table->string('exercise_name', 100);
            $table->string('category', 100);
            $table->text('description');
            $table->text('instruction');
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->enum('exercise_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('workout_programs', function (Blueprint $table) {
            $table->id('program_id');
            $table->unsignedBigInteger('trainer_id');
            $table->string('program_name', 150);
            $table->string('target_goal', 100);
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced']);
            $table->unsignedInteger('duration_weeks');
            $table->text('description');
            $table->enum('program_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->cascadeOnDelete();
        });

        Schema::create('program_exercises', function (Blueprint $table) {
            $table->id('program_exercise_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('exercise_id');
            $table->unsignedInteger('training_day');
            $table->unsignedInteger('sequence_order');
            $table->unsignedInteger('sets')->nullable();
            $table->string('repetitions', 50)->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('rest_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->foreign('program_id')->references('program_id')->on('workout_programs')->cascadeOnDelete();
            $table->foreign('exercise_id')->references('exercise_id')->on('exercises')->restrictOnDelete();
            $table->unique(['program_id', 'training_day', 'sequence_order']);
        });

        Schema::create('member_programs', function (Blueprint $table) {
            $table->id('member_program_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('trainer_id');
            $table->date('assigned_date');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->enum('program_status', ['active', 'completed', 'stopped'])->default('active');
            $table->text('trainer_notes')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('member_id')->on('members')->cascadeOnDelete();
            $table->foreign('program_id')->references('program_id')->on('workout_programs')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('trainer_id')->on('personal_trainers')->cascadeOnDelete();
            $table->index(['member_id', 'program_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_programs');
        Schema::dropIfExists('program_exercises');
        Schema::dropIfExists('workout_programs');
        Schema::dropIfExists('exercises');
    }
};
