<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipTrainerAddon extends Model
{
    protected $primaryKey = 'trainer_addon_id';

    protected $fillable = ['subscription_id', 'trainer_id', 'created_by', 'start_date', 'end_date', 'duration_months', 'session_limit', 'monthly_rate'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'monthly_rate' => 'decimal:2'];
    }

    public function subscription()
    {
        return $this->belongsTo(MembershipSubscription::class, 'subscription_id');
    }

    public function trainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id');
    }

    public function payment()
    {
        return $this->hasOne(PaymentTransaction::class, 'trainer_addon_id');
    }
}
