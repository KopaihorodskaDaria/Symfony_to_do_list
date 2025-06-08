<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class TaskController extends AbstractController
{
    #[Route('/task', name: 'task_index')]
    public function index(TaskRepository $taskRepository, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('You must be logged in to view tasks.');
        }

        $task = new Task();
        $form = $this->createForm(TaskType::class, $task, ['include_is_done' => false]);


        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($user);
            $task->setIsDone(false); // Встановлюємо як не виконану задачу
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('task_index');
        }

        $tasks = $taskRepository->findBy(['user' => $user]);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/task/delete/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Task $task, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($task->getUser() !== $user) {
            throw new AccessDeniedException('You are not allowed to delete this task.');
        }

        $em->remove($task);
        $em->flush();

        return $this->redirectToRoute('task_index');
    }

    #[Route('/task/edit/{id}', name: 'task_edit')]
    public function edit(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($task->getUser() !== $user) {
            throw new AccessDeniedException('You cannot edit this task.');
        }

        $form = $this->createForm(TaskType::class, $task, ['include_is_done' => true]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


}
