<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_NOT_FOUND)]
final class ExportNotReadyException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('La exportación no está lista.');
    }
}
