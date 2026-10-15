<?php

namespace App\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FailStaleExportsCommandTest extends KernelTestCase
{
    public function testFailingWithTheDefaultTimeoutSucceeds(): void
    {
        $tester = $this->tester();

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('more than 15 minute(s)', $tester->getDisplay());
    }

    public function testFailingWithACustomTimeoutReportsIt(): void
    {
        $tester = $this->tester();

        $tester->execute(['--minutes' => '60']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('more than 60 minute(s)', $tester->getDisplay());
    }

    #[DataProvider('invalidMinutes')]
    public function testFailingWithAnInvalidTimeoutIsRejected(string $minutes): void
    {
        $tester = $this->tester();

        $tester->execute(['--minutes' => $minutes]);

        self::assertSame(Command::INVALID, $tester->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidMinutes(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-3'];
        yield 'not a number' => ['abc'];
    }

    private function tester(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:exports:fail-stale'));
    }
}
