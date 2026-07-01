<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutProgram extends Model
{
    protected $primaryKey = 'program_id';

    protected $fillable = ['trainer_id', 'program_name', 'target_goal', 'difficulty_level', 'duration_weeks', 'description', 'program_status'];

    public function trainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id');
    }

    public function exercises()
    {
        return $this->hasMany(ProgramExercise::class, 'program_id')->orderBy('training_day')->orderBy('sequence_order');
    }

    public function assignments()
    {
        return $this->hasMany(MemberProgram::class, 'program_id');
    }
}
