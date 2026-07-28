<?php

namespace App\Dto\Company;

final class CompanyAutocompleteRequest
{
    public function __construct(
        public readonly string $name,
    ) {}
}
