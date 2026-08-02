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

    private const string PASSPORT_FORMAT = '/^[A-Za-z0-9]{6,9}$/';

    /** @var list<int> AFIP's modulo 11 multipliers for the first 10 CUIT/CUIL digits */
    private const array CUIT_CUIL_MULTIPLIERS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    /** @var list<string> AFIP's assignable CUIT/CUIL prefixes */
    private const array CUIT_CUIL_VALID_PREFIXES = ['20', '23', '24', '27', '30', '33', '34'];

    #[ORM\Column(length: 20, enumType: DocumentType::class)]
    private DocumentType $type;

    #[ORM\Column(length: 50, unique: true)]
    private string $number;

    public function __construct(DocumentType $type, string $number)
    {
        $strippedNumber = self::stripSeparators($type, $number);

        if (!self::isValid($type, $strippedNumber)) {
            throw new InvalidDocumentNumberException($type, $number);
        }

        $this->type = $type;
        $this->number = self::format($type, $strippedNumber);
    }

    public function getType(): DocumentType
    {
        return $this->type;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    private static function stripSeparators(DocumentType $type, string $number): string
    {
        return match ($type) {
            DocumentType::DNI, DocumentType::CUIT, DocumentType::CUIL => str_replace(['.', '-', ' ', ','], '', $number),
            DocumentType::PASSPORT => $number,
        };
    }

    private static function format(DocumentType $type, string $number): string
    {
        return match ($type) {
            DocumentType::CUIT, DocumentType::CUIL => self::formatCuitOrCuil($number),
            DocumentType::PASSPORT => strtoupper($number),
            default => $number,
        };
    }

    private static function formatCuitOrCuil(string $number): string
    {
        return substr($number, 0, 2) . '-' . substr($number, 2, 8) . '-' . substr($number, 10, 1);
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
        if (!preg_match('/^\d{11}$/', $number)) {
            return false;
        }

        if (!in_array(substr($number, 0, 2), self::CUIT_CUIL_VALID_PREFIXES, true)) {
            return false;
        }

        $digits = array_map(intval(...), str_split($number));
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
