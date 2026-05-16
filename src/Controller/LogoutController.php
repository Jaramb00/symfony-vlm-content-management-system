<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogoutController extends AbstractController
{
    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Symfony automatski odjavljuje korisnika
        // Preusmjeri na home nakon logou

        return $this->redirectToRoute('home');
    }
}