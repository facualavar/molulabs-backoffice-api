<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: '`companies`')]
#[ORM\UniqueConstraint(name: self::EMAIL_UNIQUE, columns: ['email'])]
#[ORM\UniqueConstraint(name: self::DOCUMENT_NUMBER_UNIQUE, columns: ['document_number'])]
class Company
{
    public const string EMAIL_UNIQUE = 'company_email_unique';
    public const string DOCUMENT_NUMBER_UNIQUE = 'company_document_number_unique';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $email;

    #[ORM\Embedded(class: Document::class, columnPrefix: 'document_')]
    private Document $document;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column]
    private readonly \DateTimeImmutable $createdAt;

    public function __construct(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone)
    {
        $this->name = $name;
        $this->email = $email;
        $this->document = new Document($documentType, $documentNumber);
        $this->phone = $phone;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->document->getType();
    }

    public function getDocumentNumber(): string
    {
        return $this->document->getNumber();
    }

    public function update(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone): void
    {
        $this->name = $name;
        $this->email = $email;
        $this->document = new Document($documentType, $documentNumber);
        $this->phone = $phone;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
