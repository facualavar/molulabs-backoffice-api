<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class CompanyHasUsersException extends \DomainException
{
    private const MESSAGE = 'La empresa tiene usuarios y no puede eliminarse.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
