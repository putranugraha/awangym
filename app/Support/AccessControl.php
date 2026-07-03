<?php

namespace App\Support;

class AccessControl
{
    public const SYSTEM_ROLES = ['admin', 'member', 'personal_trainer'];

    public const CORE_PERMISSIONS = [
        'view dashboard',
        'manage users',
        'manage roles and permissions',
        'manage members',
        'manage trainers',
        'manage packages',
        'manage memberships',
        'manage payments',
        'view reports',
        'assign workout programs',
        'view own membership',
        'view own payments',
        'view own workout program',
        'view workout catalog',
        'view assigned members',
        'validate member exercises',
    ];
}
