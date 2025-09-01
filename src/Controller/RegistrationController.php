<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use App\Service\ValidateEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private ValidateEmail $validateEmail,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    )
    {}
    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
    {

        // create a new user
        $user = new User();

        // create registration form
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        // if form is filled correct
        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // send confirm email with token
            $this->validateEmail->sendEmail($user);

            $this->addFlash('success', 'Please check your email to confirm your registration.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

}
