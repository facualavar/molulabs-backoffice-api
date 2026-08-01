<?php

namespace App\Entity;

use App\Enum\ExportStatus;
use App\Enum\ExportType;
use App\Repository\ExportJobRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExportJobRepository::class)]
#[ORM\Table(name: '`export_jobs`')]
class ExportJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType: ExportType::class)]
    private ExportType $type;

    #[ORM\Column(length: 20, enumType: ExportStatus::class)]
    private ExportStatus $status;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'jsonb')]
    private array $filters;

    #[ORM\Column(nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $requestedBy;

    #[ORM\Column]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(ExportType $type, array $filters, User $requestedBy)
    {
        $this->type = $type;
        $this->filters = $filters;
        $this->requestedBy = $requestedBy;
        $this->status = ExportStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ExportType
    {
        return $this->type;
    }

    public function getStatus(): ExportStatus
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRequestedBy(): User
    {
        return $this->requestedBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function markAsProcessing(): void
    {
        $this->status = ExportStatus::PROCESSING;
    }

    public function markAsReady(string $filePath): void
    {
        $this->status = ExportStatus::READY;
        $this->filePath = $filePath;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = ExportStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }
}
