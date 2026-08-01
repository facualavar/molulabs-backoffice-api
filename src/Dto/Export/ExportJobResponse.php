<?php

namespace App\Dto\Export;

use App\Entity\ExportJob;
use App\Enum\ExportStatus;
use App\Enum\ExportType;

final class ExportJobResponse
{
    public function __construct(
        public readonly int $id,
        public readonly ExportType $type,
        public readonly ExportStatus $status,
        public readonly ?string $errorMessage,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $completedAt,
    ) {}

    public static function fromEntity(ExportJob $job): self
    {
        return new self(
            $job->getId(),
            $job->getType(),
            $job->getStatus(),
            $job->getErrorMessage(),
            $job->getCreatedAt(),
            $job->getCompletedAt(),
        );
    }
}
