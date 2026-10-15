<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_BAD_REQUEST)]
final class CannotAssignSuperAdminRoleException extends \DomainException implements HasInvalidFieldInterface
{
    private const MESSAGE = 'El rol Superadmin no puede ser asignado.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }

    public function getField(): string
    {
        return 'role';
    }
}
