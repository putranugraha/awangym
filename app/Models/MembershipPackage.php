<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPackage extends Model
{
    protected $primaryKey = 'package_id';

    protected $fillable = ['package_name', 'duration_months', 'price', 'has_trainer', 'description', 'package_status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'has_trainer' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(MembershipSubscription::class, 'package_id');
    }
}
