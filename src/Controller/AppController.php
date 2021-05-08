<?php


namespace App\Controller;


use App\Repository\ToDoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    /**
     * @Route("/", name="app_page")
     * @param ToDoRepository $toDoRepository
     * @return Response
     */
    public function index(ToDoRepository $toDoRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $parameters['activeToDo'] = $toDoRepository->findBy(
            [
                'User' => $this->getUser()->getId(),
                'isCompleted' => false
            ],
        );

        $parameters['completedToDo'] = $toDoRepository->findBy(
            [
                'User' => $this->getUser()->getId(),
                'isCompleted' => true
            ],
        );

        return $this->render('app.html.twig', $parameters);
    }
}