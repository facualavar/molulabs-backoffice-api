<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Exception\InvalidDocumentNumberException;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Document
{
    private const int DNI_MIN = 1_000_000;
    private const int DNI_MAX = 100_000_000;

    private const string CUIT_CUIL_FORMAT = '/^\d{2}-?\d{8}-?\d$/';
    private const string PASSPORT_FORMAT = '/^[A-Za-z0-9]{6,9}$/';

    /** @var list<int> AFIP's modulo 11 multipliers for the first 10 CUIT/CUIL digits */
    private const array CUIT_CUIL_MULTIPLIERS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    #[ORM\Column(length: 20, enumType: DocumentType::class)]
    private DocumentType $type;

    #[ORM\Column(length: 50)]
    private string $number;

    public function __construct(DocumentType $type, string $number)
    {
        if (!self::isValid($type, $number)) {
            throw new InvalidDocumentNumberException($type, $number);
        }

        $this->type = $type;
        $this->number = $number;
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    private static function isValid(DocumentType $type, string $number): bool
    {
        return match ($type) {
            DocumentType::DNI => self::isValidDni($number),
            DocumentType::CUIT, DocumentType::CUIL => self::isValidCuitOrCuil($number),
            DocumentType::PASSPORT => (bool) preg_match(self::PASSPORT_FORMAT, $number),
        };
    }

    private static function isValidDni(string $number): bool
    {
        return (bool) preg_match('/^\d+$/', $number)
            && (int) $number >= self::DNI_MIN
            && (int) $number < self::DNI_MAX;
    }

    private static function isValidCuitOrCuil(string $number): bool
    {
        if (!preg_match(self::CUIT_CUIL_FORMAT, $number)) {
            return false;
        }

        $digits = array_map(intval(...), str_split(str_replace('-', '', $number)));
        $checkDigit = array_pop($digits);

        $sum = 0;
        foreach (self::CUIT_CUIL_MULTIPLIERS as $position => $multiplier) {
            $sum += $digits[$position] * $multiplier;
        }

        $expectedCheckDigit = match ($remainder = $sum % 11) {
            0 => 0,
            1 => 9,
            default => 11 - $remainder,
        };

        return $checkDigit === $expectedCheckDigit;
    }
}
