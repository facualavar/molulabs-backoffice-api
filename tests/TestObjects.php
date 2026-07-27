<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;

final class TestObjects
{
    public static function getAdminUser(Company $company): User
    {
        $user = new User(
            $company,
            'Admin',
            'Company',
            'admin@company.com',
            'password',
        );
        $user->setRoles(['ROLE_ADMIN']);

        return $user;
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

    public static function getCompany(): Company
    {
        return new Company('Acme', 'acme@company.com');
    }
}
