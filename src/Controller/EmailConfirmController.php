<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailConfirmController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/verify/email/{token}', name: 'confirm')]
    public function verify(string $token): Response
    {
        // find user using verification token
        $user = $this->userRepository->findOneBy(['emailVerificationToken' => $token]);

        // show error if user not found
        if (!$user) {
        $this->addFlash('error', 'Invalid verification token.');
        return $this->redirectToRoute('app_login');// redirect to login page
    }

        // mark user as verified
        $user->setIsVerified(true);
        // clear token
        $user->setEmailVerificationToken(null);

        //save changes
        $this->entityManager->flush();

        $this->addFlash('success', 'Your email has been confirmed!');
        return $this->redirectToRoute('app_login');
    }
}
