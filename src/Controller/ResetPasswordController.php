<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordEmailFormType;
use App\Form\ResetPasswordRequestForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Display & process form to request a password reset.
     */
    #[Route('', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        // create reset password email form
        $form = $this->createForm(ResetPasswordEmailFormType::class);
        $form->handleRequest($request);

        // if form is valid and submitted, send reset email
        if ($form->isSubmitted() && $form->isValid()) {

            $email = $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail($email, $this->mailer);
        }

        return $this->render('reset-password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Confirmation page after a user has requested a password reset.
     */
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // generate a fake token if the user does not exist
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('reset-password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Validates and process the reset URL that the user clicked in their email.
     */
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token = null): Response
    {
        if ($token) {
            // store the token in session and remove it from the URL
            // because it helps prevent the token from leaking
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        // check if token does not exist
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid
        // allow the user to change their password
        $form = $this->createForm(ResetPasswordRequestForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // a password reset token should be used only once
            $this->resetPasswordHelper->removeResetRequest($token);


            $plainPassword = $form->get('plainPassword')->getData();

            // Encode(hash) the plain password, and save it
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $this->entityManager->flush();

            // clean up the session
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('reset-password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }

    private function processSendingPasswordResetEmail(string $emailFormData): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Do not reveal if user account does not exist or not
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // show error
             $this->addFlash('reset_password_error', sprintf(
                 '%s - %s',
                 ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE,
                 $e->getReason()
             ));

            return $this->redirectToRoute('app_check_email');
        }

        // creating reset email
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply_todo@domain.com', 'Todo App Bot'))
            ->to((string) $user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('reset-password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'resetUrl' => $this->generateUrl(
                    'app_reset_password',
                    ['token' => $resetToken->getToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ])

        ;
        // send reset letter
        $this->mailer->send($email);

        // Store the token in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}
