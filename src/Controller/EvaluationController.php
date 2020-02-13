<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\Etudiant;
use App\Entity\Partie;
use App\Form\PointsType;
use App\Entity\Points;
use App\Form\EvaluationType;
use App\Entity\GroupeEtudiant;
use App\Repository\EvaluationRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/evaluation")
 */
class EvaluationController extends AbstractController
{
    /**
     * @Route("/", name="evaluation_index", methods={"GET"})
     */
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new/{id}", name="evaluation_new", methods={"GET","POST"})
     */
    public function new(Request $request, GroupeEtudiant $groupeConcerne): Response
    {

        //Création d'une évaluation vide avec tous ses composants (partie, notes)
        $evaluation = new Evaluation();
        $evaluation->setGroupe($groupeConcerne);
        $partie = new Partie();
        $partie->setIntitule("");
        $partie->setBareme(20);
        $evaluation->addPartie($partie);
        foreach ($groupeConcerne->getEtudiants() as $etudiant) {
          $note = new Points();
          $note->setValeur(0);
          $etudiant->addPoint($note);
          $partie->addNote($note);
        }


        $form = $this->createFormBuilder(['notes' => $partie->getNotes()])
            ->add('nom', TextType::class)
            ->add('date', DateType::class, [
              'widget' => 'single_text'
            ])
            ->add('notes', CollectionType::class , [
              'entry_type' => PointsType::class
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($partie);

            $entityManager->persist($evaluation);
            $entityManager->flush();

            return $this->redirectToRoute('evaluation_index');
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="evaluation_show", methods={"GET"})
     */
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="evaluation_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evaluation $evaluation): Response
    {
        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('evaluation_index');
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="evaluation_delete", methods={"GET"})
     */
    public function delete(Request $request, Evaluation $evaluation): Response
    {

        $entityManager = $this->getDoctrine()->getManager();

        //Suppression des parties associées à l'évaluation
        foreach ($evaluation->getParties() as $partie) {

          //Suppression des notes associées à la partie
          foreach ($partie->getNotes() as $note) {

            $entityManager->remove($note);

          }

          $entityManager->remove($partie);

        }

        $entityManager->remove($evaluation);
        $entityManager->flush();

        return $this->redirectToRoute('evaluation_index');
    }
}
