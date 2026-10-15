<?php

namespace App\Tests\Service;

use App\Service\UniqueValueGuard;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UniqueValueGuardTest extends TestCase
{
    public function testAssertingFreeWithNoExistingEntityDoesNotThrow(): void
    {
        $guard = new UniqueValueGuard($this->createStub(EntityManagerInterface::class));

        $guard->assertFree(null, self::entityWithId(1), static fn () => new \DomainException());

        $this->addToAssertionCount(1);
    }

    public function testAssertingFreeWithTheSameEntityDoesNotThrow(): void
    {
        $guard = new UniqueValueGuard($this->createStub(EntityManagerInterface::class));

        $guard->assertFree(self::entityWithId(1), self::entityWithId(1), static fn () => new \DomainException());

        $this->addToAssertionCount(1);
    }

    public function testAssertingFreeWithAnotherEntityThrowsTheDuplicateException(): void
    {
        $guard = new UniqueValueGuard($this->createStub(EntityManagerInterface::class));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('taken');

        $guard->assertFree(self::entityWithId(2), self::entityWithId(1), static fn () => new \DomainException('taken'));
    }

    public function testFlushingAViolationOfAKnownConstraintThrowsItsException(): void
    {
        $guard = new UniqueValueGuard($this->entityManagerFailingWith('duplicate key value violates unique constraint "known_unique"'));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('known');

        $guard->flush(['known_unique' => static fn () => new \DomainException('known')]);
    }

    public function testFlushingAViolationOfAnUnknownConstraintRethrowsTheViolation(): void
    {
        $guard = new UniqueValueGuard($this->entityManagerFailingWith('duplicate key value violates unique constraint "other_unique"'));

        $this->expectException(UniqueConstraintViolationException::class);

        $guard->flush(['known_unique' => static fn () => new \DomainException()]);
    }

    private static function entityWithId(int $id): object
    {
        return new class($id) {
            public function __construct(private readonly int $id) {}

            public function getId(): int
            {
                return $this->id;
            }
        };
    }

    private function entityManagerFailingWith(string $message): EntityManagerInterface
    {
        $violation = new UniqueConstraintViolationException(new class($message) extends \Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '23505';
            }
        }, null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException($violation);

        return $entityManager;
    }
}
