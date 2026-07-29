<?php

namespace App\Dto\Company;

use App\Enum\DocumentType;

final class CompanyRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly DocumentType $documentType,
        public readonly string $documentNumber,
        public readonly string $phone,
    ) {}
}
