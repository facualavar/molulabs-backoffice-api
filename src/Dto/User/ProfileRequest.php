<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

final class ProfileRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'El nombre no puede estar vacío.')]
        #[Assert\Length(max: 50, maxMessage: 'El nombre no puede tener más de {{ limit }} caracteres.')]
        public readonly string $firstname,

        #[Assert\NotBlank(message: 'El apellido no puede estar vacío.')]
        #[Assert\Length(max: 50, maxMessage: 'El apellido no puede tener más de {{ limit }} caracteres.')]
        public readonly string $lastname,

        #[Assert\NotBlank(message: 'El email no puede estar vacío.')]
        #[Assert\Email(message: 'El email no es válido.')]
        #[Assert\Length(max: 100, maxMessage: 'El email no puede tener más de {{ limit }} caracteres.')]
        public readonly string $email,

        #[Assert\NotBlank(message: 'El número de documento no puede estar vacío.')]
        public readonly string $documentNumber,
    ) {}
}
