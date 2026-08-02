<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class IncorrectCurrentPasswordException extends \DomainException implements HasInvalidFieldInterface
{
    public function __construct()
    {
        parent::__construct('La contraseña actual es incorrecta.');
    }

    public function getField(): string
    {
        return 'currentPassword';
    }
}
