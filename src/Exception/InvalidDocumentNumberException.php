<?php

namespace App\Exception;

use App\Enum\DocumentType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class InvalidDocumentNumberException extends \DomainException implements HasInvalidFieldInterface
{
    private const MESSAGE = '"%s" no es un número de %s válido.';

    public function __construct(DocumentType $type, string $number)
    {
        parent::__construct(\sprintf(self::MESSAGE, $number, $type->value));
    }

    public function getField(): string
    {
        return 'documentNumber';
    }
}
