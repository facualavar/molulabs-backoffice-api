<?php

namespace App\Command;

use App\Service\Export\ExportPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:exports:purge', description: 'Delete the files of the exports completed more than a number of days ago')]
final class PurgeExportsCommand extends Command
{
    private const int DEFAULT_RETENTION_DAYS = 7;

    public function __construct(private readonly ExportPurger $purger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', null, InputOption::VALUE_REQUIRED, 'Days an export file is kept', (string) self::DEFAULT_RETENTION_DAYS);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = filter_var($input->getOption('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($days === false) {
            $io->error('The number of days must be a positive integer.');

            return Command::INVALID;
        }

        $deleted = $this->purger->purgeCompletedBefore(new \DateTimeImmutable('-' . $days . ' days'));

        $io->success(sprintf('Deleted %d export file(s) older than %d day(s).', $deleted, $days));

        return Command::SUCCESS;
    }
}
