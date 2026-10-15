<?php

namespace App\Dto\Company;

use App\Enum\DocumentType;
use App\Validation\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyRequest
{
    public function __construct(
        #[Assert\NotBlank(message: ValidationMessage::NAME_BLANK)]
        #[Assert\Length(max: 50, maxMessage: ValidationMessage::NAME_TOO_LONG)]
        public readonly string $name,

        #[Assert\NotBlank(message: ValidationMessage::EMAIL_BLANK)]
        #[Assert\Email(message: ValidationMessage::EMAIL_INVALID)]
        #[Assert\Length(max: 100, maxMessage: ValidationMessage::EMAIL_TOO_LONG)]
        public readonly string $email,

        public readonly DocumentType $documentType,

        #[Assert\NotBlank(message: ValidationMessage::DOCUMENT_NUMBER_BLANK)]
        public readonly string $documentNumber,

        #[Assert\NotBlank(message: ValidationMessage::PHONE_BLANK)]
        #[Assert\Length(max: 20, maxMessage: ValidationMessage::PHONE_TOO_LONG)]
        public readonly string $phone,
    ) {}
}
