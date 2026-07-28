<?php

namespace App\Dto\User;

final class UserFilterRequest
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {}
}
