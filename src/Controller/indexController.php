<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#pre-fixe de route
#[Route('/article', name: 'article_')]
class indexController extends AbstractController
{   
    #[Route('/list', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        // $this->redirectToRoute('index');
        return $this->render('pages/index.html.twig');
    }
}