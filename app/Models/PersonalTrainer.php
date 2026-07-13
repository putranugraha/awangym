<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalTrainer extends Model
{
    protected $primaryKey = 'trainer_id';

    protected $fillable = ['user_id', 'trainer_code', 'profile_photo', 'bio', 'employment_status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function memberPrograms()
    {
        return $this->hasMany(MemberProgram::class, 'trainer_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(MembershipSubscription::class, 'trainer_id');
    }

    public function sessions()
    {
        return $this->hasMany(TrainerSession::class, 'trainer_id');
    }
}
