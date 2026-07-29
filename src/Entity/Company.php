<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: '`companies`')]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $email;

    #[ORM\Column(length: 20, enumType: DocumentType::class)]
    private DocumentType $documentType;

    #[ORM\Column(length: 50)]
    private string $documentNumber;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column]
    private readonly \DateTimeImmutable $created_at;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: User::class)]
    private Collection $users;

    public function __construct(string $name, string $email, DocumentType $documentType, string $documentNumber, string $phone)
    {
        $this->name = $name;
        $this->email = $email;
        $this->documentType = $documentType;
        $this->documentNumber = $documentNumber;
        $this->phone = $phone;
        $this->created_at = new \DateTimeImmutable();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType;
    }

    public function setDocumentType(DocumentType $documentType): void
    {
        $this->documentType = $documentType;
    }

    public function getDocumentNumber(): string
    {
        return $this->documentNumber;
    }

    public function setDocumentNumber(string $documentNumber): void
    {
        $this->documentNumber = $documentNumber;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}
