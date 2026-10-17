<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MembershipSubscription extends Model
{
    protected $primaryKey = 'subscription_id';

    protected $fillable = ['member_id', 'package_id', 'trainer_id', 'trainer_session_limit', 'created_by', 'subscription_type', 'start_date', 'end_date', 'subscription_status', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->whereHas(
            'payment',
            fn (Builder $payment) => $payment->where('payment_status', 'paid')
        );
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query
            ->paid()
            ->where('subscription_status', 'active')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());
    }

    public function trainer()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function package()
    {
        return $this->belongsTo(MembershipPackage::class, 'package_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payment()
    {
        return $this->hasOne(PaymentTransaction::class, 'subscription_id')->whereNull('trainer_addon_id');
    }

    public function trainerAddon()
    {
        return $this->hasOne(MembershipTrainerAddon::class, 'subscription_id');
    }

    public function getTrainerStartDateAttribute()
    {
        return $this->trainerAddon?->start_date ?? $this->start_date;
    }

    public function getTrainerEndDateAttribute()
    {
        return $this->trainerAddon?->end_date ?? $this->end_date;
    }

    public function scopeCurrentTrainer(Builder $query): Builder
    {
        return $query->current()->whereNotNull('trainer_id')->where(function (Builder $query) {
            $query->whereDoesntHave('trainerAddon')->orWhereHas('trainerAddon', function (Builder $addon) {
                $addon->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
                    ->whereHas('payment', fn (Builder $payment) => $payment->where('payment_status', 'paid'));
            });
        });
    }

    public function trainerSessions()
    {
        return $this->hasMany(TrainerSession::class, 'subscription_id');
    }
}
