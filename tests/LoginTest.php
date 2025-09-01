<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase

{
    public function testUserCanLoginAfterEmailConfirmation(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'test_confirm_' . uniqid() . '@example.com';

        $plainPassword = 'Password123';

        // create new user
        $user = new User();
        $user->setEmail($email);
        $user->setIsVerified(true);
        $user->setPassword(
            $container->get('security.password_hasher')->hashPassword($user, $plainPassword)
        );

        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_email' => $email,
            '_password' => $plainPassword,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/task');
        $client->followRedirect();

        $this->assertSelectorExists('h1');

        $userToRemove = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($userToRemove) {
            $entityManager->remove($userToRemove);
            $entityManager->flush();
        }
    }

    public function testUserCannotLoginWithWrongPassword(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'test_wrongpass_' . uniqid() . '@example.com';

        $plainPassword = 'Password123';
        $wrongPassword = 'WrongPassword123';

        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.password_hasher');

        $user = testHelper::createUser(
            $entityManager,
            $passwordHasher,
            $email,
            $plainPassword
        );

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_email' => $email,
            '_password' => $wrongPassword,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'Invalid credentials.');


        $attachedUser = $entityManager->getRepository(User::class)->find($user->getId());
        if ($attachedUser) {
            $entityManager->remove($attachedUser);
            $entityManager->flush();
        }
    }

    public function testUserCanLogin(): void{
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'test_login_' . uniqid() . '@example.com';

        $plainPassword = 'Password123';

        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.password_hasher');
        $user = testHelper::createUser(
            $entityManager,
            $passwordHasher,
            $email,
            $plainPassword

        );
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Sign in')->form([
            '_email' => $email,
            '_password' => $plainPassword,

        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/task');

    }

    public function testUserCannotLoginWithNoneExistingEmail(): void{
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $email = 'test_non_exis_email_' . uniqid() . '@example.com';

        $plainPassword = 'HerePassword123';

        $form = $crawler->selectButton('Sign in')->form([
            '_email' => $email,
            '_password' => $plainPassword,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'Invalid credentials.');

    }

    public function testUserCannotLoginWithUnverifiedEmail(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'test_unverified_' . uniqid() . '@example.com';

        $password = 'Password123';

        $entityManager = $container->get('doctrine')->getManager();
        $passwordHasher = $container->get('security.password_hasher');

        testHelper::createUser(
            $entityManager,
            $passwordHasher,
            $email,
            $password,
            false);

        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_email' => $email,
            '_password' => $password,
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        $this->assertSelectorTextContains('.alert', 'Please verify your email address.');

    }

}
