<?php

namespace App\Tests;

use App\Entity\User;

final class TestObjects
{
    public static function getAdminUser(): User
    {
        return new User(
            'Admin',
            'Company',
            'admin@company.com',
            'password',
        );
    }
}