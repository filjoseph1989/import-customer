<?php

namespace App\Command;

use App\Service\DataImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:customers',
    description: 'A command that will import customers from an API',
)]
class ImportCustomersCommand extends Command
{
    public function __construct(private DataImporter $dataImporter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Imports customers from a third-party API')
            ->addOption('nationality', null, InputOption::VALUE_OPTIONAL, 'The nationality of the customers', $_ENV['DEFAULT_NATIONALITY'] ?? 'AU')
            ->addOption('results', null, InputOption::VALUE_OPTIONAL, 'The number of results to fetch', $_ENV['DEFAULT_RESULTS'] ?? 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nationality = $input->getOption('nationality');
        $results = $input->getOption('results');

        if ($nationality === false || $results === false) {
            $io->warning('There is no given arguments. Check .env or use --nationality and --results.');
        }

        $this->dataImporter->importCustomers($nationality, (int) $results);
        $io->success('You have successfully imported customers.');

        return Command::SUCCESS;
    }
}
