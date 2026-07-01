<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipSubscription extends Model
{
    protected $primaryKey = 'subscription_id';

    protected $fillable = ['member_id', 'package_id', 'created_by', 'subscription_type', 'start_date', 'end_date', 'subscription_status', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
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
        return $this->hasOne(PaymentTransaction::class, 'subscription_id');
    }
}
