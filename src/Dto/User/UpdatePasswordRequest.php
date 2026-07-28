<?php

namespace App\Dto\User;

final class UpdatePasswordRequest
{
    public function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {}
}
