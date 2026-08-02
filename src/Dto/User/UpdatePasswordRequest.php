<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdatePasswordRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'La contraseña actual no puede estar vacía.')]
        public readonly string $currentPassword,

        #[Assert\NotBlank(message: 'La nueva contraseña no puede estar vacía.')]
        public readonly string $newPassword,
    ) {}
}
