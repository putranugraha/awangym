<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $user_message
 * @property string $assistant_message
 */
class WorkoutChatTurn extends Model
{
    protected $fillable = ['user_message', 'assistant_message', 'status'];
}
