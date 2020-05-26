<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\GroupeEtudiant;
use App\Repository\EvaluationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @Route("/statistiques")
 */
class StatsController extends AbstractController
{
    /**
     * @Route("/choix-statistiques", name="choix_statistiques", methods={"GET"})
     */
    public function choixStatistiques(): Response
    {
        return $this->render('evaluation/choix_statistiques.html.twig');
    }

    /**
     * @Route("/{typeStat}/choix-evaluation", name="choix_evaluation", methods={"GET", "POST"})
     */
    public function choixEvaluation($typeStat, EvaluationRepository $repoEval, Request $request): Response
    {
        $evaluations = $repoEval->findAll();
        $form = $this->createFormBuilder()
            ->add('evaluations', EntityType::class, [
                'constraints' => [new NotNull],
                'class' => Evaluation::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => $evaluations
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid()) {
            $evaluationChoisie = $form->get('evaluations')->getData();
            switch($typeStat) {
                case 'classique':
                    break;
                case 'classique-avec-parties' :
                    break;
            }
        }
        return $this->render('statistiques/choix_evaluation.html.twig', [
            'form' => $form->createView()
        ]);
    }
}