<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class CannotDeleteOwnUserException extends \DomainException
{
    private const MESSAGE = 'Un usuario no puede eliminarse a sí mismo.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
