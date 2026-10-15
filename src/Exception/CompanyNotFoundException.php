<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_NOT_FOUND)]
final class CompanyNotFoundException extends \DomainException implements HasInvalidFieldInterface
{
    private const MESSAGE = 'Empresa %s no encontrada.';

    public function __construct(?int $companyId)
    {
        parent::__construct(\sprintf(self::MESSAGE, $companyId));
    }

    public function getField(): string
    {
        return 'companyId';
    }
}
