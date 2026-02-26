<?php

namespace App\Command;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-client',
    description: 'Creates a new client interactively.',
)]
class CreateClientCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Create a New Client');

        $firstname = $io->ask('Firstname', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Firstname cannot be empty');
            }
            return $answer;
        });

        $lastname = $io->ask('Lastname', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Lastname cannot be empty');
            }
            return $answer;
        });

        $email = $io->ask('Email', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Email cannot be empty');
            }
            return $answer;
        });

        $client = new Client();
        $client->setFirstname($firstname);
        $client->setLastname($lastname);
        $client->setEmail($email);

        $errors = $this->validator->validate($client);

        if (count($errors) > 0) {
            $io->error('Validation failed:');
            foreach ($errors as $error) {
                $io->text(' - ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        try {
            $this->entityManager->persist($client);
            $this->entityManager->flush();

            $io->success('Client created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error saving client: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
