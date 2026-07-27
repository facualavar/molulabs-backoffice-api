<?php

namespace App\Controller;

use App\Dto\User\UserRequest;
use App\Dto\User\UserResponse;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CompanyRepository $companyRepository,
        private UserService $userService,
    )
    {}

    #[Route('', name: 'user_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = array_map(
            static fn (User $user) => UserResponse::fromEntity($user),
            $this->userRepository->findAll(),
        );

        return $this->json($users);
    }

    #[Route('/{user}', name: 'user_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(#[MapRequestPayload] UserRequest $request): JsonResponse
    {
        $company = $this->companyRepository->find($request->companyId);

        if (!$company) {
            throw $this->createNotFoundException('Company ' . $request->companyId . ' not found.');
        }

        $user = $this->userService->create($company, $request->firstname, $request->lastname, $request->email, $request->password, $request->role);

        return $this->json(UserResponse::fromEntity($user), Response::HTTP_CREATED);
    }

    #[Route('/{user}', name: 'user_update', methods: ['PUT'])]
    public function update(User $user, #[MapRequestPayload] UserRequest $request): JsonResponse
    {
        $user = $this->userService->update($user, $request->firstname, $request->lastname, $request->email, $request->role);

        return $this->json(UserResponse::fromEntity($user));
    }

    #[Route('/{user}', name: 'user_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
