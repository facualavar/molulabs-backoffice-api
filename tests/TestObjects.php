<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Enum\UserRole;

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
            UserRole::ADMIN,
        );
    }

    public static function getSuperAdminUser(Company $company): User
    {
        return new User(
            $company,
            'SuperAdmin',
            'Company',
            'superadmin@company.com',
            'password',
            UserRole::SUPERADMIN,
        );
    }

    public static function getUser(Company $company, string $firstname = 'Regular'): User
    {
        return new User(
            $company,
            $firstname,
            'User',
            'user@company.com',
            'password',
        );
    }

    public static function getCompany(string $name = 'Acme'): Company
    {
        return new Company($name, 'acme@company.com', DocumentType::CUIT, '30-12345678-9', '011-4444-5555');
    }
}
