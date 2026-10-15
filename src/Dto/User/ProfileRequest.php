<?php

namespace App\Dto\User;

use App\Validation\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class ProfileRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::NAME_BLANK)]
        #[Assert\Length(max: 50, maxMessage: ValidationMessage::NAME_TOO_LONG)]
        public readonly string $firstname,

        #[Assert\NotBlank(message: ValidationMessage::LASTNAME_BLANK)]
        #[Assert\Length(max: 50, maxMessage: ValidationMessage::LASTNAME_TOO_LONG)]
        public readonly string $lastname,

        #[Assert\NotBlank(message: ValidationMessage::EMAIL_BLANK)]
        #[Assert\Email(message: ValidationMessage::EMAIL_INVALID)]
        #[Assert\Length(max: 100, maxMessage: ValidationMessage::EMAIL_TOO_LONG)]
        public readonly string $email,

        #[Assert\NotBlank(message: ValidationMessage::DOCUMENT_NUMBER_BLANK)]
        public readonly string $documentNumber,
    ) {}
}
