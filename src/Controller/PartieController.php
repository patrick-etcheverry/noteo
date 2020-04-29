<?php

namespace App\Controller;

use App\Entity\Partie;
use App\Form\PartieType;
use App\Repository\PartieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/partie")
 */
class PartieController extends AbstractController
{

    /**
     * @Route("/nouvelle", name="partie_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        //Pour créer une évaluation par parties on commence par créer deux premières parties

        $partiesInitiales = [new Partie(), new Partie()]; //On commence par créer deux premieres parties dont l'utilisateur va définir le nom et le barème

        $form = $this->createFormBuilder(['parties' => $partiesInitiales])
            ->add('parties', CollectionType::class, [
                'entry_type' => PartieType::class, // Un formulaire sera créé par partie passée en paramètre du formulaire (ici deux)
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            //$entityManager = $this->getDoctrine()->getManager();
            //$entityManager->persist($partie);
            //$entityManager->flush();

            foreach ($form->getData('parties') as $data) {
                print_r($data);
            }

            //return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('partie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/modifier/{id}", name="partie_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Partie $partie): Response
    {
        $form = $this->createForm(PartieType::class, $partie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('partie_index');
        }

        return $this->render('partie/edit.html.twig', [
            'partie' => $partie,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/supprimer/{id}", name="partie_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Partie $partie): Response
    {
        if ($this->isCsrfTokenValid('delete'.$partie->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($partie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('partie_index');
    }
}
