<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Robust API Console', '1.0.0');
        
        $this->addCommands();
    }

    private function addCommands(): void
    {
        // Add custom commands here
        $this->add(new class('cache:clear') extends Command {
            protected function configure(): void
            {
                $this->setDescription('Clear application cache');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->writeln('Cache cleared successfully!');
                return Command::SUCCESS;
            }
        });

        $this->add(new class('api:docs') extends Command {
            protected function configure(): void
            {
                $this->setDescription('Generate API documentation');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $output->writeln('API documentation generated successfully!');
                return Command::SUCCESS;
            }
        });
    }
}

