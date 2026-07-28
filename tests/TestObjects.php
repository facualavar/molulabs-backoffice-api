<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;

final class TestObjects
{
    public static function getAdminUser(Company $company): User
    {
        return new User(
            $company,
            'Admin',
            'Company',
            'admin@company.com',
            'password',
            'ROLE_ADMIN',
        );
    }

    public static function getUser(Company $company): User
    {
        return new User(
            $company,
            'Regular',
            'User',
            'user@company.com',
            'password',
        );
    }

    public static function getCompany(string $name = 'Acme'): Company
    {
        return new Company($name, 'acme@company.com');
    }
}
