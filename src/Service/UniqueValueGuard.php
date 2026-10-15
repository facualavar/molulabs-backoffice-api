<?php

namespace App\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class UniqueValueGuard
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Throws when $existing is an entity other than $self: the value is already taken.
     *
     * @param callable(): \Throwable $duplicate
     */
    public function assertFree(?object $existing, object $self, callable $duplicate): void
    {
        if ($existing !== null && $existing->getId() !== $self->getId()) {
            throw $duplicate();
        }
    }

    /**
     * The lookup done before flushing catches the usual duplicate, but two requests can both pass it
     * and race to insert the same value. The loser hits the unique index: turn that violation into
     * the same domain exception the lookup would have thrown, instead of a 500.
     *
     * @param array<string, callable(): \Throwable> $exceptionsByConstraint keyed by unique constraint name
     */
    public function flush(array $exceptionsByConstraint): void
    {
        try {
            $this->entityManager->flush();
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
