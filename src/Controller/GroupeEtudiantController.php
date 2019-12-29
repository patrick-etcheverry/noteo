<?php

namespace App\Controller;

use App\Entity\GroupeEtudiant;
use App\Form\GroupeEtudiantType;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/groupe/etudiant")
 */
class GroupeEtudiantController extends AbstractController
{
    /**
     * @Route("/", name="groupe_etudiant_index", methods={"GET"})
     */
    public function index(NestedTreeRepository $nestedTreeRepository): Response
    {
        return $this->render('groupe_etudiant/index.html.twig', [
            'groupe_etudiants' => $nestedTreeRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="groupe_etudiant_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $groupeEtudiant = new GroupeEtudiant();
        $form = $this->createForm(GroupeEtudiantType::class, $groupeEtudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($groupeEtudiant);
            $entityManager->flush();

            return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/new.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="groupe_etudiant_show", methods={"GET"})
     */
    public function show(GroupeEtudiant $groupeEtudiant): Response
    {
        return $this->render('groupe_etudiant/show.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="groupe_etudiant_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {
        $form = $this->createForm(GroupeEtudiantType::class, $groupeEtudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/edit.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="groupe_etudiant_delete", methods={"DELETE"})
     */
    public function delete(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$groupeEtudiant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($groupeEtudiant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('groupe_etudiant_index');
    }
}
