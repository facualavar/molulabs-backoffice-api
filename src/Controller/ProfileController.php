<?php

namespace App\Controller;

use App\Dto\Company\CompanyRequest;
use App\Dto\Company\CompanyResponse;
use App\Dto\User\ProfileRequest;
use App\Dto\User\UpdatePasswordRequest;
use App\Dto\User\UserResponse;
use App\Entity\User;
use App\Exception\IncorrectCurrentPasswordException;
use App\Service\AuthService;
use App\Service\CompanyService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private AuthService $authService,
        private CompanyService $companyService,
    )
    {}

    #[Route('', name: 'profile_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('', name: 'profile_update', methods: ['PUT'])]
    public function update(#[CurrentUser] User $user, #[MapRequestPayload] ProfileRequest $request): JsonResponse
    {
        $user = $this->userService->update($user, $request->firstname, $request->lastname, $request->email, $request->documentNumber, $user->getRole());

        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('/password', name: 'profile_update_password', methods: ['PUT'])]
    public function updatePassword(#[CurrentUser] User $user, #[MapRequestPayload] UpdatePasswordRequest $request): JsonResponse
    {
        if (!$this->authService->isPasswordValid($user, $request->currentPassword)) {
            throw new IncorrectCurrentPasswordException();
        }

        $this->userService->updatePassword($user, $request->newPassword);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/company', name: 'profile_company_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function showCompany(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(CompanyResponse::fromEntity($user->getCompany()));
    }

    #[Route('/company', name: 'profile_company_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateCompany(#[CurrentUser] User $user, #[MapRequestPayload] CompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->update($user->getCompany(), $request->name, $request->email, $request->documentType, $request->documentNumber, $request->phone);

        return $this->json(CompanyResponse::fromEntity($company));
    }
}
