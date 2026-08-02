<?php

namespace App\Dto\User;

final class ProfileRequest
{
    public function __construct(
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly string $documentNumber,
    ) {}
}
