<?php

namespace App\Security\Voter;

use App\Entity\ExportJob;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ExportJob>
 */
final class ExportJobVoter extends Voter
{
    public const string ACCESS = 'EXPORT_JOB_ACCESS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof ExportJob && $attribute === self::ACCESS;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if (!$subject instanceof ExportJob) {
            return false;
        }

        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        return $subject->getRequestedBy()->getId() === $currentUser->getId();
    }
}
