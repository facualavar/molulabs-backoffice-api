<?php

namespace App\Controller;

use App\Dto\User\CurrentUserResponse;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/me')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class CurrentUserController extends AbstractController
{
    #[Route('', name: 'current_user_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json(CurrentUserResponse::fromEntity($user));
    }
}
