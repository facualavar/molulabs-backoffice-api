<?php

namespace App\Command;

use App\Service\Export\StaleExportFailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:exports:fail-stale', description: 'Mark as failed the exports stuck in processing for more than a number of minutes')]
final class FailStaleExportsCommand extends Command
{
    private const int DEFAULT_TIMEOUT_MINUTES = 15;

    public function __construct(private readonly StaleExportFailer $failer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('minutes', null, InputOption::VALUE_REQUIRED, 'Minutes an export may stay in processing', (string) self::DEFAULT_TIMEOUT_MINUTES);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minutes = filter_var($input->getOption('minutes'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($minutes === false) {
            $io->error('The number of minutes must be a positive integer.');

            return Command::INVALID;
        }

        $marked = $this->failer->failStartedBefore(new \DateTimeImmutable('-' . $minutes . ' minutes'));

        $io->success(sprintf('Marked %d export(s) stuck for more than %d minute(s) as failed.', $marked, $minutes));

        return Command::SUCCESS;
    }
}
