<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $primaryKey = 'exercise_id';

    protected $fillable = ['exercise_code', 'exercise_name', 'category', 'equipment', 'description', 'instruction', 'image_url', 'video_url', 'exercise_status'];

    public function programExercises()
    {
        return $this->hasMany(ProgramExercise::class, 'exercise_id');
    }
}
