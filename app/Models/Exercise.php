<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    protected $primaryKey = 'exercise_id';

    protected $fillable = ['exercise_name', 'category', 'description', 'instruction', 'image_url', 'video_url', 'exercise_status'];
}
