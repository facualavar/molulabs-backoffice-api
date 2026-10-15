<?php

namespace App\Entity;

use App\Enum\DocumentType;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[ORM\UniqueConstraint(name: self::EMAIL_UNIQUE, columns: ['email'])]
#[ORM\UniqueConstraint(name: self::DOCUMENT_NUMBER_UNIQUE, columns: ['document_number'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const string EMAIL_UNIQUE = 'user_email_unique';
    public const string DOCUMENT_NUMBER_UNIQUE = 'user_document_number_unique';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[ORM\Column(length: 20, enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Embedded(class: Document::class, columnPrefix: 'document_')]
    private Document $document;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    public function __construct(Company $company, string $firstname, string $lastname, string $email, string $documentNumber, string $password, UserRole $role = UserRole::USER)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->document = new Document(DocumentType::DNI, $documentNumber);
        $this->password = $password;
        $this->company = $company;
        $this->role = $role;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFullName(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getDocumentNumber(): string
    {
        return $this->document->getNumber();
    }

    public function setDocumentNumber(string $documentNumber): void
    {
        $this->document = new Document(DocumentType::DNI, $documentNumber);
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        return [$this->role->value];
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SUPERADMIN;
    }

    public function isPlainUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    /**
     * A superadmin manages everyone; any other user only the non-superadmins of their own company.
     */
    public function canManage(self $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return !$target->isSuperAdmin() && $target->company->getId() === $this->company->getId();
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function eraseCredentials(): void
    {
    }
}
