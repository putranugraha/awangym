<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutProgram extends Model
{
    protected $primaryKey = 'program_id';

    protected $fillable = ['program_code', 'program_name', 'target_goal', 'difficulty_level', 'duration_weeks', 'description', 'source_name', 'source_reference', 'program_status'];

    public function exercises()
    {
        return $this->hasMany(ProgramExercise::class, 'program_id')->orderBy('training_day')->orderBy('sequence_order');
    }

    public function assignments()
    {
        return $this->hasMany(MemberProgram::class, 'program_id');
    }
}
