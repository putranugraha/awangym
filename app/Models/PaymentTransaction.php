<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $primaryKey = 'transaction_id';

    public $timestamps = false;

    protected $fillable = ['invoice_number', 'member_id', 'subscription_id', 'amount', 'payment_method', 'payment_status', 'payment_date', 'verified_by', 'notes', 'created_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'datetime', 'created_at' => 'datetime'];
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function subscription()
    {
        return $this->belongsTo(MembershipSubscription::class, 'subscription_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
