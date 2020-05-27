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
        return $this->render('statistiques/choix_statistiques.html.twig');
    }

    /**
     * @Route("/{typeStat}/choix-evaluation", name="statistiques_choix_evaluation", methods={"GET", "POST"})
     */
    public function choixEvaluation($typeStat, EvaluationRepository $repoEval, Request $request): Response
    {
        switch($typeStat) {
            case 'classique':
                $evaluations = $repoEval->findAllWithOnePart();
                break;
            case 'classique-avec-parties' :
                $evaluations = $repoEval->findAllWithSeveralParts();
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
            return $this->redirectToRoute('statistiques_choisir_groupes_parties_statuts', [
                'slugEval' => $form->get('evaluations')->getData()->getSlug(),
            ]);
        }
        return $this->render('statistiques/choix_evaluation.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{slugEval}/choisir-groupes-et-statuts", name="statistiques_choisir_groupes_parties_statuts", methods={"GET","POST"})
     */
    public function choisirGroupesPartiesEtStatuts(Request $request, $slugEval, StatutRepository $repoStatut, EvaluationRepository $repoEval, GroupeEtudiantRepository $repoGroupe, PointsRepository $repoPoints ): Response
    {
        $session = $request->getSession();
        $evaluation = $repoEval->findOneBySlug($slugEval);
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
                // 'choices' => []
            ]);
        $form = $formBuilder->getForm()->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid()) {
            $groupesChoisis = $form->get("groupes")->getData();
            $statutsChoisis = $form->get("statuts")->getData();
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
                'titre' => 'Consulter les statistiques pour ' . $evaluation->getNom(),
                'plusieursEvals' => false,
                'evaluation' => $evaluation,
                'parties' => $toutesLesStats
            ]);
        }
        return $this->render('statistiques/choix_groupes_et_parties.html.twig', [
            'form' => $form->createView(),
            'evaluation' => $evaluation
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


    /**
     * @Route("/plusieurs-eval/groupes/choisir-groupe", name="stats_choisir_groupe_haut_niveau_evaluable")
     */
    public function choisirGroupesStatsPlusieursEvals(Request $request, GroupeEtudiantRepository $repoGroupe): Response
    {
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
                'class' => GroupeEtudiant::Class,
                'choice_label' => false,
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => $repoGroupe->findHighestEvaluableWith1Eval() // On choisira parmis les groupes de plus haut niveau évaluables qui ont au moins 1 évaluation que les concernent
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()  && $form->isValid())
        {
            return $this->redirectToRoute('statistiques_choisir_sous_groupes', ['slug' => $form->get('groupes')->getData()->getSlug()]);
        }
        return $this->render('evaluation/choix_groupes_plusieurs_evals.html.twig', [
            'form' => $form->createView(),
            'pasDIntentation' => true,
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
            return $this->redirectToRoute('statistiques_choisir_plusieurs_evaluations', ['slug' => $groupe->getSlug()]);
        }
        return $this->render('evaluation/choix_sous-groupes_plusieurs_evals.html.twig', [
            'groupe' => $groupe,
            'pasDIntentation' => false,
            'form' => $form->createView()]);
    }

    /**
     * @Route("/plusieurs-eval/groupes/{slug}/choisir-evaluations/", name="statistiques_choisir_plusieurs_evaluations")
     */
    public function choisirEvalsStatsPlusieursEvals(Request $request, GroupeEtudiant $groupe, PointsRepository $repoPoints): Response
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
                    'titre' => 'Consulter les statistiques sur '. count($evaluations) . ' évaluation(s)',
                    'plusieursEvals' => true,
                ]);
        }

        return $this->render('evaluation/choix_evals_plusieurs_evals_groupes.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-statut-plusieurs-evals", name="evaluations_choose_statut")
     */
    public function chooseStatutEvals(Request $request, StatutRepository $repoStatut, EvaluationRepository $repoEval): Response
    {
        $evals = $repoEval->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser());
        $statuts= array();

        foreach ($evals as $eval)
        {
            foreach ($repoStatut->findByEvaluation($eval->getId()) as $statut)
            {
                if (!in_array($statut, $statuts))
                {
                    array_push($statuts, $statut);
                }
            }
        }

        $evals = $repoEval->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser());

        foreach ($evals as $eval)
        {
            foreach ($repoStatut->findByEvaluation($eval->getId()) as $statut)
            {
                if (!in_array($statut, $statuts))
                {
                    array_push($statuts, $statut);
                }
            }
        }

        $form = $this->createFormBuilder()
            ->add('statuts', EntityType::class, [
                'class' => Statut::Class, //On veut choisir des statut
                'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
                'label' => false, // On n'affiche pas le label du champ
                'expanded' => true, // Pour avoir des boutons
                'multiple' => false, // radios
                'choices' => $statuts // On choisira parmis les groupes de plus haut niveau évaluables qui ont au moins 1 évaluation que les concernent
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            $leStatutChoisi = new Statut();

            foreach ($statuts as $statut)
            {
                if ($statut == $form->get("statuts")->getData())
                {
                    $leStatutChoisi = $statut;
                }
            }

            return $this->redirectToRoute('evaluations_statuts_choose_evals', ['slugStatut' => $leStatutChoisi->getSlug()]);
        }

        return $this->render('evaluation/choix_statut_plusieurs_evals.html.twig', ['statuts' => $statuts,'form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-evals-plusieurs-evals-statut/{slugStatut}", name="evaluations_statuts_choose_evals")
     */
    public function chooseEvalsStatutEvals(Request $request, $slugStatut, StatutRepository $repoStatut, EvaluationRepository $repoEval): Response
    {
        $statutPrincipal = $repoStatut->findOneBySlug($slugStatut);
        $toutesLesEvals = $repoEval->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser());
        $evals = array();

        foreach ($toutesLesEvals as $eval)
        {
            foreach ($eval->getGroupe()->getEtudiants() as $etudiant)
            {
                foreach($etudiant->getStatuts() as $statut)
                {
                    if ($statut == $statutPrincipal)
                    {
                        array_push($evals, $eval);
                    }
                }
            }
        }

        $toutesLesEvals = $repoEval->findOtherEvaluationsWithGradesAndCreatorAndGroup($this->getUser());

        foreach ($toutesLesEvals as $eval)
        {
            foreach ($eval->getGroupe()->getEtudiants() as $etudiant)
            {
                foreach($etudiant->getStatuts() as $statut)
                {
                    if ($statut == $statutPrincipal)
                    {
                        array_push($evals, $eval);
                    }
                }
            }
        }

        $form = $this->createFormBuilder()
            ->add('evals', EntityType::class, [
                'class' => Evaluation::Class, //On veut choisir des evaluations
                'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
                'label' => false, // On n'affiche pas le label du champ
                'expanded' => true, // Pour avoir des cases
                'multiple' => true, // à cocher
                'choices' => $evals // On choisira parmis les evaluations du groupe principal
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()  && $form->isValid())
        {
            $evalsChoisi = $form->get('evals')->getData();
            $_SESSION['evalsChoisi'] = $evalsChoisi;

            return $this->redirectToRoute('evaluations_statut_choose_stats_type',
                ['slugStatut' => $slugStatut]);
        }

        return $this->render('evaluation/choix_evals_plusieurs_evals_statut.html.twig',
            ['statutPrincipal' => $statutPrincipal,'form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-type-stats-plusieurs-evals-statut/{slugStatut}", name="evaluations_statut_choose_stats_type")
     */
    public function chooseStatsStatutEvals(Request $request, $slugStatut, StatutRepository $repoStatut, PointsRepository $repoPoints): Response
    {
        $statutPrincipal = $repoStatut->findOneBySlug($slugStatut);

        $form = $this->createFormBuilder()->add('stats', ChoiceType::class, array(
            'choices' => array(
                'Statistiques générales' => 1,
                'Evolution des résultats par étudiant' => 2),
            'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
            'label' => false, // On n'affiche pas le label du champ
            'expanded' => true, //Pour avoir des cases
            'multiple' => true //à cocher
        ))
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            /*if($form->get("stats")->getData() == 1)
            {*/
            $listeStatsParStatut = array(); // On initialise un tableau vide qui contiendra les statistiques du statut choisi

            $tabPoints = array();
            foreach ($_SESSION['evalsChoisi'] as $eval)
            {
                array_push($tabPoints, $repoPoints->findByStatut($eval->getSlug(), $slugStatut));
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
            $listeStatsParStatut[] = array("nom" => $statutPrincipal->getNom(),
                "notes" => $this->repartition($copieTabPoints),
                "allNotes" => $copieTabPoints,
                "moyenne" => $this->moyenne($copieTabPoints),
                "ecartType" => $this->ecartType($copieTabPoints),
                "minimum" => $this->minimum($copieTabPoints),
                "maximum" => $this->maximum($copieTabPoints),
                "mediane" => $this->mediane($copieTabPoints)
            );


            // Mise en session des stats
            $_SESSION['stats'] = $listeStatsParStatut;


            return $this->render('evaluation/stats_plusieurs_evals_statut.html.twig', [
                'groupes' => $listeStatsParStatut,
                'evaluations' => $_SESSION['evalsChoisi'],
                'index' => 1]); // L'index indiquant quel type de statistiques à été choisi
            /*}
            else if ($form->get("stats")->getData() == 2)
            {
              //évolution des résultats
              return $this->redirectToRoute();
            }
            else
            {
              //les 2
              return $this->redirectToRoute();
            }*/

        }
        return $this->render('evaluation/choix_stats_plusieurs_evals_statut.html.twig', ['form' => $form->createView()]);

    }
}