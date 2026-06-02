<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = [
            [
                'email' => 'admin@immopro.gn',
                'name' => 'Administrateur',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'password'
            ],
            [
                'email' => 'agent@immopro.gn',
                'name' => 'Agent Immobilier',
                'roles' => ['ROLE_AGENT'],
                'password' => 'password'
            ],
            [
                'email' => 'proprietaire@immopro.gn',
                'name' => 'Jean Propriétaire',
                'roles' => ['ROLE_PROPRIETAIRE'],
                'password' => 'password'
            ],
            [
                'email' => 'client@immopro.gn',
                'name' => 'Marie Cliente',
                'roles' => ['ROLE_CLIENT'],
                'password' => 'password'
            ],
        ];

        foreach ($users as $userData) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);

            if (!$user) {
                $user = new User();
                $user->setEmail($userData['email']);
                $user->setName($userData['name']);
                $user->setRoles($userData['roles']);
                $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
                
                $this->entityManager->persist($user);
                $output->writeln("User created: {$userData['email']} ({$userData['roles'][0]})");
            } else {
                $output->writeln("User already exists: {$userData['email']}");
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
