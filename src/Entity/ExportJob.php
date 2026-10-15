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

    /**
     * Name of the file inside the export storage, not a path.
     */
    #[ORM\Column(nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $requestedBy;

    #[ORM\Column]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

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

    public function getFileName(): ?string
    {
        return $this->fileName;
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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function markAsProcessing(): void
    {
        $this->status = ExportStatus::PROCESSING;
        $this->startedAt = new \DateTimeImmutable();
    }

    public function markAsReady(string $fileName): void
    {
        $this->status = ExportStatus::READY;
        $this->fileName = $fileName;
        $this->completedAt = new \DateTimeImmutable();
    }

    /**
     * The file was deleted to free space: the job stays READY but has nothing left to download.
     */
    public function discardFile(): void
    {
        $this->fileName = null;
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = ExportStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->completedAt = new \DateTimeImmutable();
    }
}
