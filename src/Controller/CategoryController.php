<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category/new', name: 'category_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        // create a new category and assign it to the current user
        $category = new Category();
        $category->setUser($this->getUser());

        // create category form
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            // if form is valid, save and show massage
            $this->addFlash('success', 'Category created.');
            return $this->redirectToRoute('task_index');
        }

        return $this->render('category/newCategory.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categories/delete', name: 'category_list_for_delete')]
    public function listCategoriesForDelete(CategoryRepository $categoryRepository): Response
    {
        // get all categories for deleting
        $categories = $categoryRepository->findAll();

//        $categories = $categoryRepository->findBy([
//            'user' => $this->getUser()
//        ]);

        return $this->render('category/deleteCategory.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/delete/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $em): Response
    {
//        if ($category->getUser() !== $this->getUser()) {
//            throw $this->createAccessDeniedException('You cannot delete this category.');
//        }
        // get submitted token
        $submittedToken = $request->request->get('_token');

        // validate token before confirm deleting
        if ($this->isCsrfTokenValid('delete-category' . $category->getId(), $submittedToken)) {
            $em->remove($category);
            $em->flush();

            $this->addFlash('success', 'Category deleted!.');
        } else {
            $this->addFlash('error', 'Wrong token!.');
        }

        return $this->redirectToRoute('category_list_for_delete');
    }

}
