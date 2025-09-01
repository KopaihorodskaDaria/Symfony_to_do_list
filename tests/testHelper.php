<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class testHelper
{
    public static function createUser(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        string $email,
        string $password,
        bool $isVerified = true
    ): User {

        // delete previously user with this email
        $existingUser =  $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $entityManager->remove($existingUser);
            $entityManager->flush();
        }
        // create a new user
        $user = new User();
        $user->setEmail($email);
        $user->setIsVerified($isVerified);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
