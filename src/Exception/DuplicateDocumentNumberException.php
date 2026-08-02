<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class DuplicateDocumentNumberException extends \DomainException implements HasInvalidFieldInterface
{
    public function __construct(string $documentNumber)
    {
        parent::__construct('El número de documento "' . $documentNumber . '" ya está en uso.');
    }

    public function getField(): string
    {
        return 'documentNumber';
    }
}
