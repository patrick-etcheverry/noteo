<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NoteoController extends AbstractController
{
    /**
     * @Route("/", name="noteo_login")
     */
    public function index()
    {
        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/icones", name="noteo_icones")
     */
    public function icones()
    {
        return $this->render('noteo/icones.html.twig');
    }
}
