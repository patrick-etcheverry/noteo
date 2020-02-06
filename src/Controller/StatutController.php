<?php

namespace App\Controller;

use App\Entity\Statut;
use App\Form\StatutType;
use App\Repository\StatutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/statut")
 */
class StatutController extends AbstractController
{
    /**
     * @Route("/", name="statut_index", methods={"GET"})
     */
    public function index(StatutRepository $statutRepository): Response
    {
        return $this->render('statut/index.html.twig', [
            'statuts' => $statutRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="statut_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $statut = new Statut();
        $form = $this->createForm(StatutType::class, $statut);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($statut);
            $entityManager->flush();

            return $this->redirectToRoute('statut_index');
        }

        return $this->render('statut/new.html.twig', [
            'statut' => $statut,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="statut_show", methods={"GET"})
     */
    public function show(Statut $statut): Response
    {
        return $this->render('statut/show.html.twig', [
            'statut' => $statut,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="statut_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Statut $statut): Response
    {
        $form = $this->createForm(StatutType::class, $statut);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('statut_index');
        }

        return $this->render('statut/edit.html.twig', [
            'statut' => $statut,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="statut_delete", methods={"GET"})
     */
    public function delete(Request $request, Statut $statut): Response
    {
        $manager = $this->getDoctrine()->getManager();

        foreach ($statut->getEtudiants() as $etudiant) {
          $manager->remove($etudiant);
        }
        $manager->remove($statut);
        $manager->flush();

        return $this->redirectToRoute('statut_index');
    }

}
