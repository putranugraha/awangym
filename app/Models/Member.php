<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $primaryKey = 'member_id';

    protected $fillable = ['user_id', 'member_code', 'gender', 'birth_date', 'address', 'profile_photo', 'registered_at'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'registered_at' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(MembershipSubscription::class, 'member_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'member_id');
    }

    public function programs()
    {
        return $this->hasMany(MemberProgram::class, 'member_id');
    }
}
