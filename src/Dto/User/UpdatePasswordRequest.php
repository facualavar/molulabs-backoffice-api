<?php

namespace App\Dto\User;

use App\Validation\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdatePasswordRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::CURRENT_PASSWORD_BLANK)]
        public readonly string $currentPassword,

        #[Assert\NotBlank(message: ValidationMessage::NEW_PASSWORD_BLANK)]
        public readonly string $newPassword,
    ) {}
}
