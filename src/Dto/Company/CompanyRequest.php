<?php

namespace App\Dto\Company;

final class CompanyRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}
