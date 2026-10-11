<?php

namespace App\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

trait FlushesUniqueFields
{
    /**
     * The lookup done before flushing catches the usual duplicate, but two requests can both pass it
     * and race to insert the same value. The loser hits the unique index: turn that violation into
     * the same domain exception the lookup would have thrown, instead of a 500.
     *
     * @param array<string, callable(): \Throwable> $exceptionsByConstraint keyed by unique constraint name
     */
    private function flushUnique(EntityManagerInterface $entityManager, array $exceptionsByConstraint): void
    {
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $violation) {
            foreach ($exceptionsByConstraint as $constraint => $newException) {
                if (str_contains($violation->getMessage(), '"' . $constraint . '"')) {
                    throw $newException();
                }
            }

            throw $violation;
        }
    }
}
