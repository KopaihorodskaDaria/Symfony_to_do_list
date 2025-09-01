<?php

namespace App\Tests;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class ResetPasswordTest extends WebTestCase

{
    public function testResetPasswordWithNoExistEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/reset-password');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'Send Reset Email');


        $form = $crawler->selectButton('Send Reset Email')->form([
            'reset_password_email_form[email]' => 'non_existing@example.com',
        ]);


        $client->submit($form);

        $this->assertResponseRedirects('/reset-password/check-email');
    }




    public function testResetPasswordWithValidEmail(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'reset_test_' . uniqid() . '@example.com';
        $plainPassword = 'Password123';

        // create new user
        $user = new User();
        $user->setEmail($email);
        $user->setIsVerified(true);

        $hasher = $container->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $plainPassword));

        $em = $container->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();

        // send reset password form
        $crawler = $client->request('GET', '/reset-password');

        $form = $crawler->selectButton('Send Reset Email')->form([
            'reset_password_email_form[email]' => $email,
        ]);


        $client->submit($form);

        $this->assertResponseRedirects('/reset-password/check-email');

        // delete user
        $userToRemove = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($userToRemove) {
            // delete all request that connected to user
            $resetRequests = $em->getRepository(ResetPasswordRequest::class)->findBy(['user' => $userToRemove]);
            foreach ($resetRequests as $request) {
                $em->remove($request);
            }
            $em->remove($userToRemove);
            $em->flush();
        }

    }

}
