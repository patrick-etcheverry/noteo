<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\GroupeEtudiant;
use App\Entity\Partie;
use App\Entity\Points;
use App\Entity\Statut;
use App\Repository\EvaluationRepository;
use App\Repository\GroupeEtudiantRepository;
use App\Repository\PointsRepository;
use App\Repository\StatutRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @Route("/statistiques")
 */
class StatsController extends AbstractController
{
    /**
     * @Route("/choix-statistiques", name="choix_statistiques", methods={"GET"})
     */
    public function choixStatistiques(EvaluationRepository $repoEval, StatutRepository $repoStatut, GroupeEtudiantRepository $repoGroupe): Response
    {
        //On définit quelles fonctionnalités seront disponibles à l'utilisateur
        $statsClassiquesDispo = count($repoEval->findAllWithOnePart()) >= 1; //Si plus d'une éval simple dans l'appli
        $statsClassiquesParPartiesDispo = count($repoEval->findAllWithSeveralParts()) >= 1;  //Si plus d'une éval avec partie dans l'appli
        $statsPlusieursEvalsGroupeDispo = count($repoGroupe->findAllHavingStudents()) >=1 && count($repoEval->findAll()) >= 2;  //Si plus d'un groupe avec des étudiants et plus de 2 évals dans l'appli
        $statsPlusieursEvalsStatutDispo = count($repoStatut->findAllHavingStudents()) >=1 && count($repoEval->findAll()) >= 2; //Si plus d'un statut avec des étudiants et plus de 2 évals dans l'appli
        $statsComparaisonDispo = count($repoEval->findAll()) >= 2; //Si plus de 2 évals dans l'appli

        return $this->render('statistiques/choix_statistiques.html.twig', [
            'statsClassiquesDispo' => $statsClassiquesDispo,
            'statsClassiquesParPartiesDispo' => $statsClassiquesParPartiesDispo,
            'statsPlusieursEvalsGroupeDispo' => $statsPlusieursEvalsGroupeDispo,
            'statsPlusieursEvalsStatutDispo' => $statsPlusieursEvalsStatutDispo,
            'statsComparaisonDispo' => $statsComparaisonDispo
        ]);
    }

    /**
     * @Route("/{typeStat}/{typeGraph}/choix-evaluation", name="statistiques_choix_evaluation", methods={"GET", "POST"})
     */
    public function choixEvaluation($typeStat, EvaluationRepository $repoEval, Request $request, $typeGraph): Response
    {
        //On met en sesssion le type de graphique choisi par l'utilisateur pour afficher l'onglet correspondant lors de l'affichage des stats
        $request->getSession()->set('typeGraphique', $typeGraph);
        switch($typeStat) {
            case 'classique':
                $evaluations = $repoEval->findAllWithOnePart();
                $titrePage = "Analyse d’une évaluation simple";
                $sousTitrePage = "Choisir l'évaluation pour laquelle vous désirez consulter les statistiques";
                break;
            case 'classique-avec-parties' :
                $evaluations = $repoEval->findAllWithSeveralParts();
                $titrePage = "Analyse d’une évaluation avec parties";
                $sousTitrePage = "Choisir l'évaluation pour laquelle vous désirez consulter les statistiques";
                break;
            case 'comparaison' :
                $evaluations = $repoEval->findAll();
                $titrePage = "Comparaison des résultats d’une évaluation spécifique à un ensemble d’évaluations";
                $sousTitrePage = "Choisir l'évaluation de référence qui sera comparée à un ensemble d’évaluations";
                break;
        }
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
            switch ($typeStat) {
                case 'classique':
                case 'classique-avec-parties' :
                    return $this->redirectToRoute('statistiques_classiques_choisir_groupes_parties_statuts', [
                        'slug' => $form->get('evaluations')->getData()->getSlug(),
                        'typeStat' => $typeStat
                    ]);
                    break;
                case 'comparaison' :
                    return $this->redirectToRoute('statistiques_comparaison_choisir_groupes_statuts', [
                        'slug' => $form->get('evaluations')->getData()->getSlug(),
                    ]);
                    break;
            }
        }
        return $this->render('statistiques/choix_evaluation.html.twig', [
            'form' => $form->createView(),
            'titrePage' => $titrePage,
            'sousTitrePage' => $sousTitrePage
        ]);
    }

    /**
     * @Route("/comparaison/{slug}/choisir-groupes-et-statuts", name="statistiques_comparaison_choisir_groupes_statuts", methods={"GET","POST"})
     */
    public function choisirGroupesEtStatutscomparaison(Request $request, Evaluation $evaluation, StatutRepository $repoStatut,GroupeEtudiantRepository $repoGroupe): Response
    {
        $session = $request->getSession();
        $groupeConcerne = $evaluation->getGroupe();
        //On récupère la liste de tous les enfants (directs et indirects) du groupe concerné pour choisir ceux sur lesquels on veut des statistiques
        $choixGroupe = $repoGroupe->findAllOrderedFromNode($groupeConcerne);
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
                'class' => GroupeEtudiant::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $choixGroupe // On choisira parmis le groupe concerné et ses enfants
            ])
            ->add('statuts', EntityType::class, [
                'class' => Statut::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $repoStatut->findByEvaluation($evaluation->getId()) // On choisira parmis les statuts qui possède au moins 1 étudiant ayant participé à l'évaluation
                // 'choices' => []
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid()) {
            $session->set('groupesChoisis', $form->get('groupes')->getData());
            $session->set('statutsChoisis', $form->get('statuts')->getData());
            return $this->redirectToRoute('statistiques_comparaison_choisir_autres_evals', [
                'slug' => $evaluation->getSlug(),
            ]);
        }
        return $this->render('statistiques/choix_groupes_et_parties.html.twig', [
            'form' => $form->createView(),
            'evaluation' => $evaluation,
            'pasDeChoixParties' => true,
            "titrePage" => "Comparaison des résultats d’une évaluation spécifique à un ensemble d’évaluations"
        ]);
    }

    /**
     * @Route("/comparaison/{slug}/choisir-autres-evals", name="statistiques_comparaison_choisir_autres_evals", methods={"GET","POST"})
     */
    public function choisirEvalsComparaison(Request $request, Evaluation $evaluationConcernee, EvaluationRepository $repoEval, PointsRepository $repoPoints ): Response
    {
        $form = $this->createFormBuilder()
            ->add('evaluations', EntityType::class, [
                'constraints' => [new NotNull],
                'class' => Evaluation::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $repoEval->findAllOverAGroupExceptCurrentOne($evaluationConcernee->getGroupe()->getId(),$evaluationConcernee->getId())
            ])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if(count($form->get('evaluations')->getData()) > 0) {
                $session = $request->getSession();
                $tabStatsComparaison = array();
                $evaluationsChoisies =  $form->get('evaluations')->getData();
                $groupes = $session->get('groupesChoisis');
                $statuts = $session->get('statutsChoisis');

                foreach ($groupes as $groupe) {
                    // déterminer la moyenne du groupe courant à l'évaluation que l'on veut comparer aux autres
                    $pointsEvaluationGroupe = $repoPoints->findAllNotesByGroupe($evaluationConcernee->getId(), $groupe->getId());
                    $moyenneEvaluationCouranteGroupe = array();
                    foreach ($pointsEvaluationGroupe as $note) {
                        $moyenneEvaluationGroupe[] = $note["valeur"];
                    }
                    $moyenneEvaluationCouranteGroupe = $this->moyenne($moyenneEvaluationGroupe);

                    //déterminer la moyenne des moyennes aux évaluations
                    $moyennesGroupeTmp = array();

                    foreach ($evaluationsChoisies as $evaluationCourante) { // pour chaque évaluation, on détermine sa moyenne pour le groupe courant
                        //determiner la moyenne de l'évaluation courante
                        $tabPoints = $repoPoints->findAllNotesByGroupe($evaluationCourante->getId(), $groupe->getId()); // on récupère les notes
                        //on crée un tableau temporaire ou on stoque séparement chaque note
                        $copieTab = array();
                        foreach ($tabPoints as $note) {
                            $copieTab[] = $note["valeur"];
                        }
                        $moyenneEvaluationCourante = $this->moyenne($copieTab); // on determine la moyenne du controle courant
                        array_push($moyennesGroupeTmp, $moyenneEvaluationCourante);
                    }
                    //on détermine la moyenne des moyennes
                    $moyenneDesMoyennesEvaluations = $this->moyenne($moyennesGroupeTmp);
                    $tabStatsComparaison[] = [
                        "nom" => $groupe->getNom(),
                        "moyenneControleCourant" => $moyenneEvaluationCouranteGroupe,
                        "moyenneAutresControles" => $moyenneDesMoyennesEvaluations,
                    ];
                }

                ///on traite les statistiques pour tous les statuts
                foreach ($statuts as $statut) {
                    /// déterminer la moyenne du groupe courant à l'évaluation
                    $pointsEvaluationStatut = $repoPoints->findAllNotesByStatut($evaluationConcernee->getId(), $statut->getId());
                    $moyenneEvaluationCouranteStatut = array();
                    foreach ($pointsEvaluationStatut as $note) {
                        $moyenneEvaluationCouranteStatut[] = $note["valeur"];
                    }
                    $moyenneEvaluationCouranteStatut = $this->moyenne($moyenneEvaluationCouranteStatut);
                    /// déterminer la moyenne des moyennes aux évaluations
                    $moyennesTmp = array();

                    foreach ($evaluationsChoisies as $evaluationCourante) { // pour chaque évaluation, on détermine sa moyenne pour le groupe courant
                        //determiner la moyenne de l'évaluation courante
                        $tabPoints = $repoPoints->findAllNotesByStatut($evaluationCourante->getId(), $statut->getId()); // on récupère les notes
                        //on crée un tableau temporaire ou on stoque séparement chaque note
                        $copieTab = array();
                        foreach ($tabPoints as $note) {
                            $copieTab[] = $note["valeur"];
                        }
                        $moyenneEvaluationCourante = $this->moyenne($copieTab); // on determine la moyenne du controle courant
                        array_push($moyennesTmp, $moyenneEvaluationCourante);
                    }
                    //on détermine la moyenne des moyennes
                    $moyenneDesMoyennesEvaluations = $this->moyenne($moyennesTmp);
                    $tabStatsComparaison[] = [
                        "nom" => $statut->getNom(),
                        "moyenneControleCourant" => $moyenneEvaluationCouranteStatut,
                        "moyenneAutresControles" => $moyenneDesMoyennesEvaluations,
                    ];
                }
                $formatAdapteALaVue = [[
                    "nom" => "Comparaison des évaluations",
                    "stats" => $tabStatsComparaison
                ]
                ];
                return $this->render('statistiques/statsComparaison.html.twig', [
                    'evaluations' => $evaluationsChoisies,
                    'evaluationConcernee' => $evaluationConcernee,
                    'groupes' => $groupes,
                    "parties" => $formatAdapteALaVue,
                    'titre' => "Comparer " . (count($evaluationsChoisies)+1) . ' évaluations',
                    'plusieursEvals' => true,
                ]);
            }
        }

        return $this->render('statistiques/choix_plusieurs_evals_comparaison.html.twig', [
            'form' => $form->createView(),
            'titrePage' => "Comparaison des résultats d’une évaluation spécifique à un ensemble d’évaluations",
            'evaluation' => $evaluationConcernee,
        ]);
    }

    /**
     * @Route("/{typeStat}/{slug}/choisir-groupes-et-statuts", name="statistiques_classiques_choisir_groupes_parties_statuts", methods={"GET","POST"})
     */
    public function choisirGroupesPartiesEtStatuts(Request $request, $typeStat, Evaluation $evaluation, StatutRepository $repoStatut, GroupeEtudiantRepository $repoGroupe, PointsRepository $repoPoints ): Response
    {
        switch($typeStat) {
            case 'classique':
                $titrePage = "Analyse d’une évaluation simple";
                break;
            case 'classique-avec-parties' :
                $titrePage = "Analyse d’une évaluation avec parties";
                break;
        }
        $session = $request->getSession();
        $groupeConcerne = $evaluation->getGroupe();
        //On récupère la liste de tous les enfants (directs et indirects) du groupe concerné pour choisir ceux sur lesquels on veut des statistiques
        $choixGroupe = $repoGroupe->findAllOrderedFromNode($groupeConcerne);
        $formBuilder = $this->createFormBuilder();
        if(count($evaluation->getParties()) > 1) {
            $formBuilder
                ->add('parties', EntityType::class, [
                'class' => Partie::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $evaluation->getParties() // On choisira parmis le groupe concerné et ses enfants
            ]);
        }
        $formBuilder
            ->add('groupes', EntityType::class, [
                'class' => GroupeEtudiant::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $choixGroupe // On choisira parmis le groupe concerné et ses enfants
            ])
            ->add('statuts', EntityType::class, [
                'class' => Statut::Class,
                'choice_label' => false,
                'label' => false,
                'mapped' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $repoStatut->findByEvaluation($evaluation->getId()) // On choisira parmis les statuts qui possède au moins 1 étudiant ayant participé à l'évaluation
            ]);
        $form = $formBuilder->getForm()->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid()) {
            $groupesChoisis = $form->get("groupes")->getData();
            $statutsChoisis = $form->get("statuts")->getData();
            if(count($groupesChoisis) >0 || count($statutsChoisis) > 0) {
                if(count($evaluation->getParties()) > 1) {
                    $partiesChoisies = $form->get("parties")->getData();
                }
                else {
                    $partiesChoisies = $evaluation->getParties();
                }
                $toutesLesStats = [];
                //Calcul des stats pour toutes les parties
                foreach($partiesChoisies as $partie) {
                    $statsDuGroupePourLaPartie = [];
                    foreach ($groupesChoisis as $groupe) {
                        $notesGroupe = $repoPoints->findByGroupeAndPartie($evaluation->getId(), $groupe->getId(), $partie->getId());
                        //On fait une copie du résultat de la requête pour simplifier le format de renvoi utilisé par doctrine
                        $copieTabPoints = array();
                        foreach ($notesGroupe as $element) {
                            $copieTabPoints[] = $element["valeur"];
                        }
                        $statsDuGroupePourLaPartie[] = [
                            "nom" => $groupe->getNom(),
                            "repartition" => $this->repartition($copieTabPoints),
                            "listeNotes" => $copieTabPoints,
                            "moyenne" =>$this->moyenne($copieTabPoints),
                            "ecartType" =>$this->ecartType($copieTabPoints),
                            "minimum"=>$this->minimum($copieTabPoints),
                            "maximum"=>$this->maximum($copieTabPoints),
                            "mediane"=>$this->mediane($copieTabPoints),
                        ] ;
                    }
                    $statsDuStatutPourLaPartie = [];
                    foreach ($statutsChoisis as $statut) {
                        $notesStatut = $repoPoints->findByStatutAndPartie($evaluation->getId(), $statut->getId(), $partie->getId());
                        //On fait une copie du résultat de la requête pour simplifier le format de renvoi utilisé par doctrine
                        $copieTabPoints = array();
                        foreach ($notesStatut as $element) {
                            $copieTabPoints[] = $element["valeur"];
                        }
                        $statsDuStatutPourLaPartie[] = [
                            "nom" => $statut->getNom(),
                            "repartition" => $this->repartition($copieTabPoints),
                            "listeNotes" => $copieTabPoints,
                            "moyenne" =>$this->moyenne($copieTabPoints),
                            "ecartType" =>$this->ecartType($copieTabPoints),
                            "minimum"=>$this->minimum($copieTabPoints),
                            "maximum"=>$this->maximum($copieTabPoints),
                            "mediane"=>$this->mediane($copieTabPoints),
                        ];
                    }
                    //Ajout des stats de la partie (groupe + statut) dans le tableau général
                    $toutesLesStats[] = [
                        "nom" => $partie->getIntitule(),
                        "bareme" => $partie->getBareme(),
                        "stats" => array_merge($statsDuGroupePourLaPartie, $statsDuStatutPourLaPartie)
                    ];
                }
                //Mise en session des stats pour le mail et la page de visualisation
                $session->set('stats',$toutesLesStats);
                return $this->render('statistiques/stats.html.twig', [
                    'titrePage' => 'Statistiques pour ' . $evaluation->getNom(),
                    'plusieursEvals' => false,
                    'evaluation' => $evaluation,
                    'parties' => $toutesLesStats
                ]);
            }
        }
        return $this->render('statistiques/choix_groupes_et_parties.html.twig', [
            'form' => $form->createView(),
            'evaluation' => $evaluation,
            'pasDeChoixParties' => false,
            'titrePage' => $titrePage
        ]);
    }

    /**
     * @Route("/previsualisation-mail/{slug}", name="previsualisation_mail", methods={"GET"})
     */
    public function previsualisationMail(Evaluation $evaluation): Response
    {
        $nbEtudiants = count($evaluation->getGroupe()->getEtudiants());
        $nomGroupe = $evaluation->getGroupe()->getNom();
        $this->denyAccessUnlessGranted('EVALUATION_PREVISUALISATION_MAIL', $evaluation);
        return $this->render('evaluation/previsualisationMail.html.twig',[
            'evaluation' => $evaluation,
            'nbEtudiants' => $nbEtudiants,
            'nomGroupe' => $nomGroupe
        ]);
    }

    /**
     * @Route("/exemple-mail/{id}", name="exemple_mail", methods={"GET"})
     */
    public function exempleMail(Request $request, Evaluation $evaluation, PointsRepository $pointsRepository): Response
    {
        $this->denyAccessUnlessGranted('EVALUATION_EXEMPLE_MAIL', $evaluation);
        // Récupération de la session
        $session = $request->getSession();
        // Récupération des stats mises en session
        $stats = $session->get('stats');
        $notesEtudiants = $pointsRepository->findNotesAndEtudiantByEvaluation($evaluation);
        $tabRang = $pointsRepository->findAllNotesByGroupe($evaluation->getId(),$evaluation->getGroupe()->getId());
        $copieTabRang = array();
        foreach ($tabRang as $element) {
            $copieTabRang[] = $element["valeur"];
        }
        $effectif = sizeof($copieTabRang);
        $noteEtudiant = $notesEtudiants[0]->getValeur();
        $position = array_search($noteEtudiant, $copieTabRang) + 1;
        $mailAdmin = $_ENV['MAIL_ADMINISTRATEUR'];
        return $this->render('evaluation/mailEnvoye.html.twig',[
            'etudiantsEtNotes' => $notesEtudiants[0],
            'stats' => $stats,
            'position' => $position,
            'effectif' => $effectif,
            'mailAdmin' =>  $mailAdmin
        ]);
    }

    /**
     * @Route("/envoi-mail/{slug}", name="envoi_mail", methods={"GET"})
     */
    public function envoiMail(Request $request, Evaluation $evaluation, PointsRepository $pointsRepository, \Swift_Mailer $mailer): Response
    {
        $this->denyAccessUnlessGranted('EVALUATION_ENVOI_MAIL', $evaluation);
        // Récupération de la session
        $session = $request->getSession();
        // Récupération des stats mises en session
        $stats = $session->get('stats');
        $notesEtudiants = $pointsRepository->findNotesAndEtudiantByEvaluation($evaluation);
        $tabRang = $pointsRepository->findAllNotesByGroupe($evaluation->getId(),$evaluation->getGroupe()->getId());
        $copieTabRang = array();
        foreach ($tabRang as $element) {
            $copieTabRang[] = $element["valeur"];
        }
        $effectif = sizeof($copieTabRang);
        $mailAdmin = $_ENV['MAIL_ADMINISTRATEUR'];
        for ($i=0; $i < count($notesEtudiants); $i++) {
            $noteEtudiant = $notesEtudiants[$i]->getValeur();
            $position = array_search($noteEtudiant, $copieTabRang) + 1;
            $message = (new \Swift_Message('Noteo - ' . $evaluation->getNom()))
                ->setFrom($_ENV['UTILISATEUR_SMTP'])
                ->setTo($notesEtudiants[$i]->getEtudiant()->getMail())
                ->setBody(
                    $this->renderView('evaluation/mailEnvoye.html.twig',[
                        'etudiantsEtNotes' => $notesEtudiants[$i],
                        'stats' => $stats,
                        'position' => $position,
                        'effectif' => $effectif,
                        'mailAdmin' => $mailAdmin
                    ]),'text/html');
            $mailer->send($message);
        }
        $this->addFlash(
            'info',
            'L\'envoi des mails a été effectué avec succès.'
        );
        return $this->render('statistiques/stats.html.twig', [
            'titre' => 'Consulter les statistiques pour' . $evaluation->getNom(),
            'plusieursEvals' => false,
            'parties' => $stats,
            'evaluation' => $evaluation
        ]);
    }

    /**
     * @Route("/plusieurs-eval/groupes/{typeGraph}/choisir-groupe", name="stats_choisir_groupe_haut_niveau_evaluable")
     */
    public function choisirGroupeStatsPlusieursEvals(Request $request, GroupeEtudiantRepository $repoGroupe, $typeGraph): Response
    {
        //On met en sesssion le type de graphique choisi par l'utilisateur pour afficher l'onglet correspondant lors de l'affichage des stats
        $request->getSession()->set('typeGraphique', $typeGraph);
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
                'class' => GroupeEtudiant::Class,
                'choice_label' => false,
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => $repoGroupe->findHighestEvaluableWith1EvalOrMore() // On choisira parmis les groupes de plus haut niveau évaluables qui ont au moins 1 évaluation que les concernent
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid())
        {
            return $this->redirectToRoute('statistiques_choisir_sous_groupes', ['slug' => $form->get('groupes')->getData()->getSlug()]);
        }
        return $this->render('statistiques/choix_groupes_plusieurs_evals.html.twig', [
            'form' => $form->createView(),
            'pasDIndentation' => true,
        ]);
    }

    /**
     * @Route("/plusieurs-eval/groupes/{slug}/choisir-sous-groupes", name="statistiques_choisir_sous_groupes")
     */
    public function choisirSousGroupesStatsPlusieursEvals(Request $request, GroupeEtudiant $groupe, GroupeEtudiantRepository $repoGroupe): Response
    {
        $groupesAChoisir = $repoGroupe->findAllOrderedFromNode($groupe);
        array_shift($groupesAChoisir); //On ne veut pas avoir le groupe choisi dans le choix
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
                'constraints' => [
                    new NotBlank()
                ],
                'class' => GroupeEtudiant::Class,
                'choice_label' => false,
                'label' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $groupesAChoisir // On choisira parmis les sous groupes du groupe choisi au préalable
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            $sousGroupes = $form->get('groupes')->getData();
            $request->getSession()->set('sousGroupes', $sousGroupes);
            return $this->redirectToRoute('statistiques_groupes_choisir_plusieurs_evaluations', ['slug' => $groupe->getSlug()]);
        }
        return $this->render('statistiques/choix_sous-groupes_plusieurs_evals.html.twig', [
            'groupe' => $groupe,
            'pasDIndentation' => false,
            'form' => $form->createView()]);
    }

    /**
     * @Route("/plusieurs-eval/groupes/{slug}/choisir-evaluations/", name="statistiques_groupes_choisir_plusieurs_evaluations")
     */
    public function choisirEvalsGroupePlusieursEvals(Request $request, GroupeEtudiant $groupe, PointsRepository $repoPoints): Response
    {
        $form = $this->createFormBuilder()
            ->add('evaluations', EntityType::class, [
                'class' => Evaluation::Class,
                'choice_label' => false,
                'label' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $groupe->getEvaluations() // On choisira parmis les evaluations du groupe principal
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            if(count($form->get('evaluations')->getData()) > 0) {
                $evaluations = $form->get('evaluations')->getData();
                $listeStatsParGroupe = array(); // On initialise un tableau vide qui contiendra les statistiques des groupes choisis
                $lesGroupes = array(); // On regroupe le groupe principal et les sous groupes pour faciliter la requete
                array_push($lesGroupes, $groupe);
                foreach ($request->getSession()->get('sousGroupes') as $sousGroupe)
                {
                    array_push($lesGroupes, $sousGroupe);
                }
                foreach ($lesGroupes as $groupe) // On récupère les notes du groupe principal et des sous groupes sur toutes les évaluations choisis
                {
                    $tabPoints = array();
                    foreach ($evaluations as $eval)
                    {
                        array_push($tabPoints, $repoPoints->findAllNotesByGroupe($eval->getId(), $groupe->getId()));
                    }
                    //On crée une copie de tabPoints qui contiendra les valeurs des notes pour simplifier le tableau renvoyé par la requete
                    $copieTabPoints = array();
                    foreach ($tabPoints as $element)
                    {
                        foreach ($element as $point)
                        {
                            foreach ($point as $note)
                            {
                                $copieTabPoints[] = $note;
                            }
                        }
                    }
                    //On remplit le tableau qui contiendra toutes les statistiques du groupe
                    $listeStatsParGroupe[] = array("nom" => $groupe->getNom(),
                        "repartition" => $this->repartition($copieTabPoints),
                        "listeNotes" => $copieTabPoints,
                        "moyenne" => $this->moyenne($copieTabPoints),
                        "ecartType" => $this->ecartType($copieTabPoints),
                        "minimum" => $this->minimum($copieTabPoints),
                        "maximum" => $this->maximum($copieTabPoints),
                        "mediane" => $this->mediane($copieTabPoints)
                    );
                }
                $formatStatsPourLaVue = [["nom" => "Évaluations", "bareme" => 20, "stats" => $listeStatsParGroupe]];
                return $this->render('statistiques/stats.html.twig', [
                    'parties' => $formatStatsPourLaVue,
                    'evaluations' => $evaluations,
                    'groupes' => $lesGroupes,
                    'titrePage' => 'Consulter les statistiques sur '. count($evaluations) . ' évaluation(s)',
                    'plusieursEvals' => true,
                ]);
            }
        }

        return $this->render('statistiques/choix_evals_plusieurs_evals_groupes.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/plusieurs-eval/statuts/{typeGraph}/choisir-statut", name="stats_choisir_statut")
     */
    public function choisirStatutsEvaluable(Request $request, StatutRepository $repoStatut, $typeGraph): Response
    {
        //On met en sesssion le type de graphique choisi par l'utilisateur pour afficher l'onglet correspondant lors de l'affichage des stats
        $request->getSession()->set('typeGraphique', $typeGraph);
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
                'class' => Statut::Class, //On veut choisir des statut
                'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
                'label' => false, // On n'affiche pas le label du champ
                'expanded' => true, // Pour avoir des boutons
                'multiple' => false, // radios
                'choices' => $repoStatut->findAllWith1EvalOrMore() // On choisira parmis les groupes de plus haut niveau évaluables qui ont au moins 1 évaluation que les concernent
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            return $this->redirectToRoute('statistiques_statuts_choisir_plusieurs_evaluations', [
                'slug' => $form->get('groupes')->getData()->getSlug()
            ]);
        }

        return $this->render('statistiques/choix_statut_plusieurs_evals.html.twig', [
            'form' => $form->createView(),
            'pasDIndentation' => true
        ]);
    }

    /**
     * @Route("/plusieurs-eval/statuts/{slug}/choisir-evaluations/", name="statistiques_statuts_choisir_plusieurs_evaluations")
     */
    public function choisirEvalsStatutPlusieursEvals(Request $request, Statut $statut, EvaluationRepository $repoEval, PointsRepository $repoPoints): Response
    {
        $form = $this->createFormBuilder()
            ->add('evaluations', EntityType::class, [
                'class' => Evaluation::Class, //On veut choisir des evaluations
                'constraints' => [
                    new NotBlank()
                ],
                'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
                'label' => false, // On n'affiche pas le label du champ
                'expanded' => true, // Pour avoir des cases
                'multiple' => true, // à cocher
                'choices' => $repoEval->findAllByStatut($statut->getId()) // On choisira parmis les evaluations du groupe principal
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            if(count($form->get('evaluations')->getData()) > 0) {
                $evaluations = $form->get('evaluations')->getData();
                $listeStatsParStatut = array(); // On initialise un tableau vide qui contiendra les statistiques du statut choisi
                $tabPoints = array();
                foreach ($evaluations as $eval)
                {
                    array_push($tabPoints, $repoPoints->findAllNotesByStatut($eval->getId(), $statut->getId()));
                }
                //On crée une copie de tabPoints qui contiendra les valeurs des notes pour simplifier le tableau renvoyé par la requete
                $copieTabPoints = array();
                foreach ($tabPoints as $element)
                {
                    foreach ($element as $point)
                    {
                        foreach ($point as $note)
                        {
                            $copieTabPoints[] = $note;
                        }
                    }
                }
                //On remplit le tableau qui contiendra toutes les statistiques du groupe
                $listeStatsParStatut[] = array("nom" => $statut->getNom(),
                    "repartition" => $this->repartition($copieTabPoints),
                    "listeNotes" => $copieTabPoints,
                    "moyenne" => $this->moyenne($copieTabPoints),
                    "ecartType" => $this->ecartType($copieTabPoints),
                    "minimum" => $this->minimum($copieTabPoints),
                    "maximum" => $this->maximum($copieTabPoints),
                    "mediane" => $this->mediane($copieTabPoints)
                );

                $formatStatsPourLaVue = [["nom" => "Évaluations", "bareme" => 20, "stats" => $listeStatsParStatut]];
                return $this->render('statistiques/stats.html.twig', [
                    'parties' => $formatStatsPourLaVue,
                    'evaluations' => $evaluations,
                    'groupes' => $statut,
                    'titre' => 'Consulter les statistiques sur '. count($evaluations) . ' évaluation(s)',
                    'plusieursEvals' => true,
                ]);
            }
        }

        return $this->render('statistiques/choix_evals_plusieurs_evals_statut.html.twig', [
            'form' => $form->createView(),
            'statut' => $statut
        ]);
    }

    public function repartition($tabPoints) {
        $repartition = array(0,0,0,0,0);
        foreach ($tabPoints as $note) {
            if ($note >= 0 && $note < 4) {
                $repartition[0]++;
            }
            if ($note >= 4 && $note < 8) {
                $repartition[1]++;
            }
            if ($note >= 8 && $note < 12) {
                $repartition[2]++;
            }
            if ($note >= 12 && $note < 16) {
                $repartition[3]++;
            }
            if ($note >= 16 && $note <= 20) {
                $repartition[4]++;
            }
        }
        return $repartition;
    }

    public function moyenne($tabPoints)
    {
        $moyenne = 0;
        $nbNotes = 0;
        foreach($tabPoints as $note)
        {
            $nbNotes++;
            $moyenne += $note;
        }
        if($nbNotes != 0){
            $moyenne = $moyenne/$nbNotes;
        }
        else {
            $moyenne = 0;
        }

        return round($moyenne,2);
    }

    public function ecartType($tabPoints)
    {
        $moyenne = $this->moyenne($tabPoints);
        $nbNotes = 0;
        $ecartType = 0;
        foreach($tabPoints as $note)
        {
            $ecartType = $ecartType + pow(($note - $moyenne), 2);
            $nbNotes++;
        }
        if ($nbNotes != 0) {
            $ecartType = sqrt($ecartType/$nbNotes);
        }
        else {
            $ecartType = 0;
        }

        return round($ecartType,2);
    }

    public function minimum($tabPoints)
    {
        if (!empty($tabPoints)) {
            $min = 20;
            foreach($tabPoints as $note)
            {
                if ($note < $min)
                {
                    $min = $note;
                }
            }
        }
        else {
            $min = 0;
        }

        return $min;
    }

    public function maximum($tabPoints)
    {
        $max = 0;
        foreach($tabPoints as $note)
        {
            if ($note > $max)
            {
                $max = $note;
            }
        }
        return $max;
    }

    public function mediane($tabPoints)
    {
        $mediane = 0;
        $nbValeurs = count($tabPoints);
        if(!empty($tabPoints)) {
            if ($nbValeurs % 2 == 1) //Si il y a un nombre impair de valeurs, la médiane vaut la valeur au milieu du tableau
            {
                $mediane = $tabPoints[intval($nbValeurs/2)];
            }
            else //Si c'est pair, la mediane vaut la demi-somme des 2 valeurs centrales
            {
                $mediane = ($tabPoints[($nbValeurs-1)/2] + $tabPoints[($nbValeurs)/2])/2;
            }
        }
        else {
            $mediane = 0;
        }
        return $mediane;
    }
}