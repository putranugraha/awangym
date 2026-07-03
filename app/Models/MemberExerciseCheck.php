<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberExerciseCheck extends Model
{
    protected $primaryKey = 'check_id';

    protected $fillable = ['member_program_id', 'program_exercise_id', 'validated_by', 'validated_at', 'notes'];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime'];
    }

    public function memberProgram()
    {
        return $this->belongsTo(MemberProgram::class, 'member_program_id');
    }

    public function programExercise()
    {
        return $this->belongsTo(ProgramExercise::class, 'program_exercise_id');
    }

    public function validator()
    {
        return $this->belongsTo(PersonalTrainer::class, 'validated_by');
    }
}
