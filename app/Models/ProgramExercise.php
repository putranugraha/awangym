<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramExercise extends Model
{
    protected $primaryKey = 'program_exercise_id';

    public $timestamps = false;

    protected $fillable = ['program_id', 'exercise_id', 'training_day', 'session_name', 'sequence_order', 'sets', 'repetitions', 'duration_minutes', 'rest_seconds', 'intensity', 'notes'];

    public function program()
    {
        return $this->belongsTo(WorkoutProgram::class, 'program_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

    public function checks()
    {
        return $this->hasMany(MemberExerciseCheck::class, 'program_exercise_id');
    }
}
