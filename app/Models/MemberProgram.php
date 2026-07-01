<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberProgram extends Model
{
    protected $primaryKey = 'member_program_id';

    protected $fillable = ['member_id', 'program_id', 'trainer_id', 'assigned_date', 'start_date', 'end_date', 'progress_percentage', 'program_status', 'trainer_notes'];

    protected function casts(): array
    {
        return ['assigned_date' => 'date', 'start_date' => 'date', 'end_date' => 'date', 'progress_percentage' => 'decimal:2'];
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function program()
    {
        return $this->belongsTo(WorkoutProgram::class, 'program_id');
    }

    public function trainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id');
    }
}
