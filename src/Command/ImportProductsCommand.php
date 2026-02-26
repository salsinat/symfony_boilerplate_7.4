<?php

namespace App\Command;

use App\Service\CsvImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-products',
    description: 'Imports products from a CSV file',
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private CsvImportService $csvImportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        $io->title('Importing Products from ' . $filePath);

        try {
            $result = $this->csvImportService->importProducts($filePath);

            if (!empty($result['errors'])) {
                $io->warning('Some products could not be imported:');
                $io->listing($result['errors']);
            }

            $io->success(sprintf('Import complete! %d products imported.', $result['imported']));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
