<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class DuplicateEmailException extends \DomainException implements HasInvalidFieldInterface
{
    private const MESSAGE = 'El email "%s" ya está en uso.';

    public function __construct(string $email)
    {
        parent::__construct(\sprintf(self::MESSAGE, $email));
    }

    public function getField(): string
    {
        return 'email';
    }
}
