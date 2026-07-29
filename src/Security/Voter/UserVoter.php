<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
final class UserVoter extends Voter
{
    public const string ACCESS = 'USER_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof User && $attribute === self::ACCESS;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$subject instanceof User) {
            return false;
        }

        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        if ($currentUser->getRole() === UserRole::SUPERADMIN) {
            return true;
        }

        return $subject->getCompany()->getId() === $currentUser->getCompany()->getId();
    }
}
