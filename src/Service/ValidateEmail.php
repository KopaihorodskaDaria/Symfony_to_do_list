<?php
namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ValidateEmail{
    public function __construct(
        private MailerInterface $mailer,
//        private RequestStack $request,
        private UrlGeneratorInterface $router,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function sendEmail(User $user):void {

        $token = $this->generateToken();
        $user->setEmailVerificationToken($token);
        $this->entityManager->flush();

        $linkConfirm= $this->router->generate('confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
        $email = (new TemplatedEmail())
            ->from('no-reply@example.com')
            ->to($user->getEmail())
            ->subject('Please verify your email address')
            ->htmlTemplate('registration/confirm.html.twig')
            ->context(['user' => $user, 'linkConfirm' => $linkConfirm]);


        $this->mailer->send($email);

    }

    private function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}


