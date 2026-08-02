<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_NOT_FOUND)]
final class CompanyNotFoundException extends \DomainException implements HasInvalidFieldInterface
{
    public function __construct(?int $companyId)
    {
        parent::__construct('Empresa ' . $companyId . ' no encontrada.');
    }

    public function getField(): string
    {
        return 'companyId';
    }
}
