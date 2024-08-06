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
            ->addOption('nationality', null, InputOption::VALUE_OPTIONAL, 'The nationality of the customers', 'AU')
            ->addOption('results', null, InputOption::VALUE_OPTIONAL, 'The number of results to fetch', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nationality = $input->getOption('nationality');
        $results = $input->getOption('results');

        $io = new SymfonyStyle($input, $output);
        $this->dataImporter->importCustomers($nationality, (int) $results);
        $io->success('You have successfully imported customers.');

        return Command::SUCCESS;
    }
}
