<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class CannotAssignSuperAdminRoleException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Role ROLE_SUPERADMIN cannot be assigned.');
    }
}
