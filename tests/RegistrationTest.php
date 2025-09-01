<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationTest extends WebTestCase

{
    public function testRegisterPageLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Register');
    }

    public function testSuccessfulRegistrationRedirectsToLogin(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $email = 'testing@example.com';
        $password = 'Password123';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $password,
            'registration_form[plainPassword][second]' => $password,
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
    }

    public function testRegistrationWithEmptyEmail(): void{
        $client = static::createClient();
        $crawler=$client->request('GET', '/register');

        $email='';
        $plainPassword='Password1212';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'Enter a valid email address');

    }

    public function testRegistrationWithShortEmail(): void{
        $client = static::createClient();
        $crawler=$client->request('GET', '/register');

        $email='x@y.z';
        $plainPassword='Password1212';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'Email is too short, email must contain at least 6 letter.');

    }

    public function testRegistrationWithTooLongEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $email = str_repeat('a', 101) . '@example.com';
        $plainPassword = 'Password123';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'Length cannot be longer than 100 characters');
    }


    public function testRegistrationWithInvalidEmail(): void{
        $client = static::createClient();
        $crawler=$client->request('GET', '/register');
        $email='test1';
        $plainPassword='Password1212';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'Email should be example@example.com');

    }

    public function testRegistrationWithExistingEmail(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();

        $email = 'testing@example.com';
        $plainPassword = 'Password123';

        // creating user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword(
            $container->get('security.password_hasher')->hashPassword($user, $plainPassword)
        );
        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        // register with the same email
        $crawler = $client->request('GET', '/register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'Account with this email already exists');

        // delete testing user
        $entityManager->remove($user);
        $entityManager->flush();
    }


    public function testRegistrationWithInvalidPassword(): void{
        $client = static::createClient();
        $crawler=$client->request('GET', '/register');

        $email = 'test2@example.com';
        $plainPassword = 'pass';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);

        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', 'The password must contain at least one uppercase letter, one lowercase letter, and one digit, and minimum 6 characters.');

    }

    public function testRegistrationWithEmptyPassword(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $email = 'test@example.com';
        $plainPassword = '';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('ul li', '`Password should not be empty.`');
    }

    public function testRegistrationWithDifferentPassword(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');
        $email = 'test3@example.com';

        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => 'Password1212',
            'registration_form[plainPassword][second]' => 'NoPassword1213',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form', 'The passwords do not match.');
    }

    public function testUserRegistration(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $crawler = $client->request('GET', '/register');

        $email='test@test.com';
        $plainPassword='Password123';


        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Register');
        $form = $crawler->selectButton('Register')->form([
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $plainPassword,
            'registration_form[plainPassword][second]' => $plainPassword,
        ]);
        $client->submit($form);
        $this->assertResponseRedirects('/login');

        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $this->assertNotNull($user, 'User was not created');


        $this->assertContains('ROLE_USER', $user->getRoles());


        $this->assertNotEquals($plainPassword, $user->getPassword());


        $passwordHasher = $container->get('security.password_hasher');
        $this->assertTrue($passwordHasher->isPasswordValid($user, $plainPassword));


        $entityManager = $container->get('doctrine')->getManager();
        $entityManager->remove($user);
        $entityManager->flush();

    }


}
