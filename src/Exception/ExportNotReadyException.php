<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_NOT_FOUND)]
final class ExportNotReadyException extends \DomainException
{
    private const MESSAGE = 'La exportación no está lista.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
