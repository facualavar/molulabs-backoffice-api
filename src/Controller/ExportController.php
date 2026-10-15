<?php

namespace App\Controller;

use App\Dto\Export\ExportJobResponse;
use App\Entity\ExportJob;
use App\Enum\ExportStatus;
use App\Exception\ExportNotReadyException;
use App\Http\NotFoundMessage;
use App\Security\Voter\ExportJobVoter;
use App\Service\Export\ExportStorage;
use League\Flysystem\UnableToReadFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/exports')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ExportController extends AbstractController
{
    #[Route('/{exportJob}', name: 'export_show', methods: ['GET'])]
    #[IsGranted(ExportJobVoter::ACCESS, subject: 'exportJob', statusCode: 404, message: NotFoundMessage::EXPORT)]
    public function show(ExportJob $exportJob): JsonResponse
    {
        return $this->json(ExportJobResponse::fromEntity($exportJob));
    }

    #[Route('/{exportJob}/download', name: 'export_download', methods: ['GET'])]
    #[IsGranted(ExportJobVoter::ACCESS, subject: 'exportJob', statusCode: 404, message: NotFoundMessage::EXPORT)]
    public function download(ExportJob $exportJob, ExportStorage $storage): StreamedResponse
    {
        $fileName = $exportJob->getFileName();

        if ($exportJob->getStatus() !== ExportStatus::READY || !$fileName) {
            throw new ExportNotReadyException();
        }

        try {
            $input = $storage->readStream($fileName);
        } catch (UnableToReadFile) {
            throw new ExportNotReadyException();
        }

        return new StreamedResponse(
            static function () use ($input): void {
                fpassthru($input);
                fclose($input);
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName),
            ],
        );
    }
}
