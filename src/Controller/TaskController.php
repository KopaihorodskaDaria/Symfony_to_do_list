<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\CategoryRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class TaskController extends AbstractController
{
    private function getCurrentUser()
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->getUser();
    }

    public function __construct(
        private TaskRepository $taskRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/task', name: 'task_index')]
    public function index(Request $request): Response
    {
        $user = $this->getCurrentUser();

        $categoryId = $request->query->get('category');
        $searchWord = $request->query->get('search');

        $qb = $this->taskRepository->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($categoryId) {
            $qb->join('t.category', 'c')
                ->andWhere('c.id = :category')
                ->andWhere('c.user = :userCategory OR c.user IS NULL')
                ->setParameter('category', $categoryId);

        }

        if ($searchWord) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(t.title)', ':searchWord'),
                    $qb->expr()->like('LOWER(t.description)', ':searchWord')
                )
            )
            ->setParameter('searchWord', '%' . strtolower($searchWord) . '%');

        }

        $tasks = $qb->getQuery()->getResult();

        $categories = $this->categoryRepository->createQueryBuilder('c')
            ->where('c.user = :user OR c.user IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $form = $this->createForm(TaskType::class);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'categories' => $categories,
            'currentCategory' => $categoryId,
            'form' => $form->createView(),
            'searchWord' => $searchWord,
        ]);
    }

    #[Route('/task/new', name: 'task_new')]
    public function new(Request $request): Response
    {
        $user = $this->getCurrentUser();

        $task = new Task();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUser($user);
            $task->setStatus('todo');

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->addFlash('success', 'Task was successfully created.');

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/task/delete/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Task $task): Response
    {
        $user = $this->getCurrentUser();
        if ($task->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to delete this task.');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $this->addFlash('success', 'Task was successfully deleted.');

        return $this->redirectToRoute('task_index');
    }

    #[Route('/task/edit/{id}', name: 'task_edit')]
    public function edit(Task $task, Request $request): Response
    {
        $user = $this->getCurrentUser();

        if ($task->getUser() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to edit this task.');
        }


        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->flush();

            $this->addFlash('success', 'Task was successfully updated.');

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/task/{id}/change-status', name: 'task_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Task $task): Response
    {
        $user = $this->getCurrentUser();

        if ($task->getUser() !== $user) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        if (!in_array($status, ['todo', 'in_progress', 'done'])) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        if ($task->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], 403);
        }

        $task->setStatus($status);

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/task/statistic', name: 'task_statistic')]
    public function statistic(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();


        $statusTitle = ['todo', 'in_progress', 'done'];
        $data = [];

        foreach ($statusTitle as $status) {
            $count = $this->taskRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->andWhere('t.status = :status')
                ->setParameter('user', $user)
                ->setParameter('status', $status)
                ->getQuery()
                ->getSingleScalarResult();

            $data[$status] = $count;
        }

        return $this->render('task/statistic.html.twig', [
            'data' => $data,
        ]);
    }


}
