<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class DuplicateEmailException extends \DomainException implements HasInvalidFieldInterface
{
    public function __construct(string $email)
    {
        parent::__construct('El email "' . $email . '" ya está en uso.');
    }

    public function getField(): string
    {
        return 'email';
    }
}
