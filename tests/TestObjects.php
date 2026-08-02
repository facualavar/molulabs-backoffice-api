<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\ExportJob;
use App\Entity\User;
use App\Enum\DocumentType;
use App\Enum\ExportType;
use App\Enum\UserRole;

final class TestObjects
{
    private static int $dniCounter = 40_000_000;
    private static int $cuitBodyCounter = 10_000_000;

    public static function getAdminUser(Company $company, ?string $documentNumber = null): User
    {
        return new User(
            $company,
            'Admin',
            'Company',
            'admin@company.com',
            $documentNumber ?? self::nextDni(),
            'password',
            UserRole::ADMIN,
        );
    }

    public static function getSuperAdminUser(Company $company, ?string $documentNumber = null): User
    {
        return new User(
            $company,
            'SuperAdmin',
            'Company',
            'superadmin@company.com',
            $documentNumber ?? self::nextDni(),
            'password',
            UserRole::SUPERADMIN,
        );
    }

    public static function getUser(Company $company, string $firstname = 'Regular', ?string $documentNumber = null): User
    {
        return new User(
            $company,
            $firstname,
            'User',
            'user@company.com',
            $documentNumber ?? self::nextDni(),
            'password',
        );
    }

    public static function getCompany(string $name = 'Acme', ?string $documentNumber = null): Company
    {
        return new Company($name, 'acme@company.com', DocumentType::CUIT, $documentNumber ?? self::nextCuit(), '011-4444-5555');
    }

    public static function getExportJob(User $requestedBy, ExportType $type = ExportType::USERS): ExportJob
    {
        return new ExportJob($type, [], $requestedBy);
    }

    private static function nextDni(): string
    {
        return (string) self::$dniCounter++;
    }

    private static function nextCuit(): string
    {
        $body = str_pad((string) self::$cuitBodyCounter++, 8, '0', STR_PAD_LEFT);

        return '30-' . $body . '-' . self::cuitCheckDigit('30' . $body);
    }

    private static function cuitCheckDigit(string $first10): int
    {
        $multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        foreach (str_split($first10) as $position => $digit) {
            $sum += (int) $digit * $multipliers[$position];
        }

        return match ($remainder = $sum % 11) {
            0 => 0,
            1 => 9,
            default => 11 - $remainder,
        };
    }
}
