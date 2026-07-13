<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainerSession extends Model
{
    protected $primaryKey = 'trainer_session_id';

    protected $fillable = ['subscription_id', 'trainer_id', 'session_date', 'notes'];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function subscription()
    {
        return $this->belongsTo(MembershipSubscription::class, 'subscription_id');
    }

    public function trainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id');
    }
}
