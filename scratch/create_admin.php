<?php

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
/** @var UserPasswordHasherInterface $hasher */
$hasher = $container->get('security.user_password_hasher');

$email = 'admin@immopro.gn';
$user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

if (!$user) {
    $user = new User();
    $user->setEmail($email);
    $user->setName('Administrateur');
    $user->setRoles(['ROLE_ADMIN']);
    $user->setPassword($hasher->hashPassword($user, 'password'));
    
    $em->persist($user);
    $em->flush();
    echo "Admin created: admin@immopro.gn / password\n";
} else {
    echo "Admin already exists.\n";
}
