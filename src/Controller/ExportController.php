<?php

namespace App\Controller;

use App\Dto\Export\ExportJobResponse;
use App\Entity\ExportJob;
use App\Enum\ExportStatus;
use App\Exception\ExportNotReadyException;
use App\Http\NotFoundMessage;
use App\Security\Voter\ExportJobVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function download(ExportJob $exportJob): BinaryFileResponse
    {
        if ($exportJob->getStatus() !== ExportStatus::READY || !$exportJob->getFilePath()) {
            throw new ExportNotReadyException();
        }

        $response = new BinaryFileResponse($exportJob->getFilePath(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($exportJob->getFilePath()),
        );

        return $response;
    }
}
