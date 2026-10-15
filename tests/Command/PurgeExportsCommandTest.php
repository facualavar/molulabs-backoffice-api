<?php

namespace App\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeExportsCommandTest extends KernelTestCase
{
    public function testPurgingWithTheDefaultRetentionSucceeds(): void
    {
        $tester = $this->tester();

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('older than 7 day(s)', $tester->getDisplay());
    }

    public function testPurgingWithACustomRetentionReportsIt(): void
    {
        $tester = $this->tester();

        $tester->execute(['--days' => '30']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('older than 30 day(s)', $tester->getDisplay());
    }

    #[DataProvider('invalidDays')]
    public function testPurgingWithAnInvalidRetentionIsRejected(string $days): void
    {
        $tester = $this->tester();

        $tester->execute(['--days' => $days]);

        self::assertSame(Command::INVALID, $tester->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDays(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-3'];
        yield 'not a number' => ['abc'];
    }

    private function tester(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:exports:purge'));
    }
}
