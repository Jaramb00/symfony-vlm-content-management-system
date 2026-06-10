<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        return $this->render('home.html.twig');
    }

    #[Route('/register', name: 'page_register')]
    public function register(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/login', name: 'page_login')]
    public function login(): Response
    {
        return $this->render('auth/login.html.twig');
    }

    #[Route('/dashboard', name: 'page_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard/index.html.twig');
    }

    #[Route('/request/{id}', name: 'page_request_detail')]
    public function requestDetail(int $id): Response
    {
        return $this->render('dashboard/detail.html.twig', ['requestId' => $id]);
    }

    #[Route('/analytics', name: 'page_analytics')]
    public function analytics(): Response
    {
    return $this->render('analytics/index.html.twig');
    }
}