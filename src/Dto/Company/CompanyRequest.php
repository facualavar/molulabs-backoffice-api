<?php

namespace App\Dto\Company;

use App\Enum\DocumentType;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'El nombre no puede estar vacío.')]
        #[Assert\Length(max: 50, maxMessage: 'El nombre no puede tener más de {{ limit }} caracteres.')]
        public readonly string $name,

        #[Assert\NotBlank(message: 'El email no puede estar vacío.')]
        #[Assert\Email(message: 'El email no es válido.')]
        #[Assert\Length(max: 100, maxMessage: 'El email no puede tener más de {{ limit }} caracteres.')]
        public readonly string $email,

        public readonly DocumentType $documentType,

        #[Assert\NotBlank(message: 'El número de documento no puede estar vacío.')]
        public readonly string $documentNumber,

        #[Assert\NotBlank(message: 'El teléfono no puede estar vacío.')]
        #[Assert\Length(max: 20, maxMessage: 'El teléfono no puede tener más de {{ limit }} caracteres.')]
        public readonly string $phone,
    ) {}
}
