<?php

namespace App\Controller;

use App\Dto\Company\CompanyRequest;
use App\Dto\Company\CompanyResponse;
use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Service\CompanyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/companies')]
final class CompanyController extends AbstractController
{
    public function __construct(
        private CompanyRepository $companyRepository,
        private CompanyService $companyService,
    )
    {}

    #[Route('', name: 'company_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $companies = array_map(
            static fn (Company $company) => CompanyResponse::fromEntity($company),
            $this->companyRepository->findAll(),
        );

        return $this->json($companies);
    }

    #[Route('/{company}', name: 'company_show', methods: ['GET'])]
    public function show(Company $company): JsonResponse
    {
        return $this->json(CompanyResponse::fromEntity($company));
    }

    #[Route('', name: 'company_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] CompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->create($request->name, $request->email);

        return $this->json(CompanyResponse::fromEntity($company), Response::HTTP_CREATED);
    }

    #[Route('/{company}', name: 'company_update', methods: ['PUT'])]
    public function update(Company $company, #[MapRequestPayload] CompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->update($company, $request->name, $request->email);

        return $this->json(CompanyResponse::fromEntity($company));
    }

    #[Route('/{company}', name: 'company_delete', methods: ['DELETE'])]
    public function delete(Company $company): JsonResponse
    {
        $this->companyService->delete($company);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
