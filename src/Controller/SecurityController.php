<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    /**
     * Страница авторизации
     *
     * @Route("/login", name="app_login")
     */
    public function login(): Response
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('app_page');
        }

        return $this->render('auth.html.twig');
    }

    /**
     * Страница выхода
     *
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
