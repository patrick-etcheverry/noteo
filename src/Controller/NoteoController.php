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
        return $this->render('noteo/login.html.twig');
    }
}
