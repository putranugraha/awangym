<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPackage extends Model
{
    protected $primaryKey = 'package_id';

    protected $fillable = ['package_name', 'duration_months', 'price', 'description', 'package_status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function subscriptions()
    {
        return $this->hasMany(MembershipSubscription::class, 'package_id');
    }
}
