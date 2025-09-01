<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // if user is authenticated then show his tasks.
        if ($this->getUser()) {
            return $this->redirectToRoute('task_index');
        }
        // if not - show main page
        return $this->render('home/index.html.twig');
    }
}

