<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class DuplicateDocumentNumberException extends \DomainException implements HasInvalidFieldInterface
{
    private const MESSAGE = 'El número de documento "%s" ya está en uso.';

    public function __construct(string $documentNumber)
    {
        parent::__construct(\sprintf(self::MESSAGE, $documentNumber));
    }

    public function getField(): string
    {
        return 'documentNumber';
    }
}
