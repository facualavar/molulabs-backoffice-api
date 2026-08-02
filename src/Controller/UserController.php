<?php

namespace App\Controller;

use App\Dto\Export\ExportJobResponse;
use App\Dto\Pagination\PaginatedResponse;
use App\Dto\User\UserFilterRequest;
use App\Dto\User\UserRequest;
use App\Dto\User\UserResponse;
use App\Entity\Company;
use App\Entity\User;
use App\Enum\ExportType;
use App\Exception\CompanyNotFoundException;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use App\Service\ExportService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CompanyRepository $companyRepository,
        private UserService $userService,
        private ExportService $exportService,
    )
    {}

    #[Route('', name: 'user_list', methods: ['GET'])]
    public function index(#[CurrentUser] User $currentUser, #[MapQueryString] UserFilterRequest $filter): JsonResponse
    {
        $company = $currentUser->isSuperAdmin() ? null : $currentUser->getCompany();

        $result = $this->userRepository->findByFilters($company, $filter->name, $filter->role, $filter->documentNumber, $filter->page, $filter->limit);

        return $this->json(PaginatedResponse::fromResult(
            $result,
            static fn (User $user) => UserResponse::fromEntity($user),
        ));
    }

    #[Route('/export', name: 'user_export', methods: ['POST'])]
    public function export(#[CurrentUser] User $currentUser, #[MapQueryString] UserFilterRequest $filter): JsonResponse
    {
        $companyId = $currentUser->isSuperAdmin() ? null : $currentUser->getCompany()->getId();

        $job = $this->exportService->create(ExportType::USERS, [
            'name' => $filter->name,
            'role' => $filter->role?->value,
            'companyId' => $companyId,
        ], $currentUser);

        return $this->json(ExportJobResponse::fromEntity($job), Response::HTTP_ACCEPTED);
    }

    #[Route('/{user}', name: 'user_show', methods: ['GET'])]
    #[IsGranted(UserVoter::ACCESS, subject: 'user', statusCode: 404, message: 'User not found.')]
    public function show(User $user): JsonResponse
    {
        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(#[CurrentUser] User $currentUser, #[MapRequestPayload] UserRequest $request): JsonResponse
    {
        $company = $currentUser->isSuperAdmin()
            ? $this->findCompany($request->companyId)
            : $currentUser->getCompany();

        $user = $this->userService->create($company, $request->firstname, $request->lastname, $request->email, $request->documentNumber, $request->password, $request->role);

        return $this->json(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[Route('/{user}', name: 'user_update', methods: ['PUT'])]
    #[IsGranted(UserVoter::ACCESS, subject: 'user', statusCode: 404, message: 'User not found.')]
    public function update(User $user, #[MapRequestPayload] UserRequest $request): JsonResponse
    {
        $user = $this->userService->update($user, $request->firstname, $request->lastname, $request->email, $request->documentNumber, $request->role);

        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('/{user}', name: 'user_delete', methods: ['DELETE'])]
    #[IsGranted(UserVoter::ACCESS, subject: 'user', statusCode: 404, message: 'User not found.')]
    public function delete(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function findCompany(?int $companyId): Company
    {
        $company = $companyId ? $this->companyRepository->find($companyId) : null;

        if (!$company) {
            throw new CompanyNotFoundException($companyId);
        }

        return $company;
    }
}
