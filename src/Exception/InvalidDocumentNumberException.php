<?php

namespace App\Exception;

use App\Enum\DocumentType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class InvalidDocumentNumberException extends \DomainException
{
    public function __construct(DocumentType $type, string $number)
    {
        parent::__construct(\sprintf('"%s" is not a valid %s number.', $number, $type->value));
    }
}
