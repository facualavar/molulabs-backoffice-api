<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class CompanyHasUsersException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('La empresa tiene usuarios y no puede eliminarse.');
    }
}
