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
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
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

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(type: 'jsonb')]
    private array $roles = [];

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
        $this->roles = [$role->value];
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
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRole(): UserRole
    {
        return UserRole::from($this->roles[0]);
    }

    public function setRole(UserRole $role): void
    {
        $this->roles = [$role->value];
    }

    public function isSuperAdmin(): bool
    {
        return $this->getRole() === UserRole::SUPERADMIN;
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
