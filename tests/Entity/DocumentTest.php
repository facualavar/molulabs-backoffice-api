<?php

namespace App\Tests\Entity;

use App\Entity\Document;
use App\Enum\DocumentType;
use App\Exception\InvalidDocumentNumberException;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testCreatingDocumentReturnsGivenTypeAndNumber(): void
    {
        $document = new Document(DocumentType::DNI, '12345678');

        self::assertSame(DocumentType::DNI, $document->getType());
        self::assertSame('12345678', $document->getNumber());
    }

    public function testCreatingDniAtLowerBoundaryDoesNotThrow(): void
    {
        $document = new Document(DocumentType::DNI, '1000000');

        self::assertSame('1000000', $document->getNumber());
    }

    public function testCreatingDniJustBelowUpperBoundaryDoesNotThrow(): void
    {
        $document = new Document(DocumentType::DNI, '99999999');

        self::assertSame('99999999', $document->getNumber());
    }

    public function testCreatingDniBelowLowerBoundaryThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::DNI, '999999');
    }

    public function testCreatingDniAtUpperBoundaryThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::DNI, '100000000');
    }

    public function testCreatingDniWithNonNumericValueThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::DNI, '1234567a');
    }

    public function testCreatingDniWithDotsNormalizesNumberWithoutDots(): void
    {
        $document = new Document(DocumentType::DNI, '12.345.678');

        self::assertSame('12345678', $document->getNumber());
    }

    public function testCreatingDniWithSpacesNormalizesNumberWithoutSpaces(): void
    {
        $document = new Document(DocumentType::DNI, '12 345 678');

        self::assertSame('12345678', $document->getNumber());
    }

    public function testCreatingDniWithCommasNormalizesNumberWithoutCommas(): void
    {
        $document = new Document(DocumentType::DNI, '12,345,678');

        self::assertSame('12345678', $document->getNumber());
    }

    public function testCreatingCuitWithValidCheckDigitDoesNotThrow(): void
    {
        $document = new Document(DocumentType::CUIT, '30-12345678-1');

        self::assertSame('30-12345678-1', $document->getNumber());
    }

    public function testCreatingCuitWithoutDashesNormalizesNumberWithDashes(): void
    {
        $document = new Document(DocumentType::CUIT, '30987654321');

        self::assertSame('30-98765432-1', $document->getNumber());
    }

    public function testCreatingCuilWithoutDashesNormalizesNumberWithDashes(): void
    {
        $document = new Document(DocumentType::CUIL, '20112233440');

        self::assertSame('20-11223344-0', $document->getNumber());
    }

    public function testCreatingCuitWithInvalidCheckDigitThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::CUIT, '30-12345678-9');
    }

    public function testCreatingCuitWithInvalidPrefixThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::CUIT, '99-12345678-1');
    }

    public function testCreatingCuitWithInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::CUIT, 'not-a-cuit');
    }

    public function testCreatingCuilWithValidCheckDigitDoesNotThrow(): void
    {
        $document = new Document(DocumentType::CUIL, '20-11223344-0');

        self::assertSame('20-11223344-0', $document->getNumber());
    }

    public function testCreatingCuilWithInvalidCheckDigitThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::CUIL, '20-11223344-9');
    }

    public function testCreatingPassportWithValidNumberDoesNotThrow(): void
    {
        $document = new Document(DocumentType::PASSPORT, 'AB123456');

        self::assertSame('AB123456', $document->getNumber());
    }

    public function testCreatingPassportWithLowercaseLettersNormalizesNumberToUppercase(): void
    {
        $document = new Document(DocumentType::PASSPORT, 'ab123456');

        self::assertSame('AB123456', $document->getNumber());
    }

    public function testCreatingPassportBelowMinimumLengthThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::PASSPORT, 'AB12');
    }

    public function testCreatingPassportAboveMaximumLengthThrowsException(): void
    {
        $this->expectException(InvalidDocumentNumberException::class);

        new Document(DocumentType::PASSPORT, 'AB1234567890');
    }
}
