<?php

namespace App\Controller;

use App\Form\PointsType;
use App\Entity\Evaluation;
use App\Entity\Partie;
use App\Entity\Points;
use App\Entity\GroupeEtudiant;
use App\Repository\PartieRepository;
use App\Repository\PointsRepository;
use App\Repository\EvaluationRepository;
use App\Repository\GroupeEtudiantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;


/**
 * @Route("/evaluation")
 */
class EvaluationController extends AbstractController
{
    /**
     * @Route("/mes-evaluations", name="evaluation_enseignant", methods={"GET"})
     */
    public function indexEnseignantConnecte(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser()),
            'mine' => true
        ]);
    }

    /**
     * @Route("/autres-evaluations", name="evaluation_autres", methods={"GET"})
     */
    public function indexAutres(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findOtherEvaluationsWithGradesAndCreatorAndGroup($this->getUser()),
            'mine' => false
        ]);
    }

    /**
     * @Route("/nouvelle/{slug}", name="evaluation_new", methods={"GET","POST"})
     */
    public function new(Request $request, GroupeEtudiant $groupeConcerne): Response
    {
        //Création d'une évaluation vide avec tous ses composants (partie, notes(définies à 0 par défaut))
        $evaluation = new Evaluation();
        $evaluation->setGroupe($groupeConcerne);
        $partie = new Partie();
        $partie->setIntitule("Évaluation");
        $partie->setBareme(20);
        $evaluation->addPartie($partie);
        foreach ($groupeConcerne->getEtudiants() as $etudiant) {
          $note = new Points();
          $note->setValeur(0);
          $etudiant->addPoint($note);
          $partie->addNote($note);
        }
        //Création du formulaire pour saisir les informations de l'évaluation (le formulaire n'est pas lié à une entité)
        $form = $this->createFormBuilder(['notes' => $partie->getNotes()])
            ->add('nom', TextType::class,[
              'constraints' => [
                  new NotBlank,
                  new Length(['max' => 255]),
                  new Regex(['pattern' => '/[a-zA-Z0-9]/', 'message' => 'Le nom de l\'évaluation doit contenir au moins un chiffre ou une lettre'])
              ]
            ])
            ->add('date', DateType::class, [
              'widget' => 'choice',
              'years' => range(date("Y")-5, date("Y")+1),
              'data' => new \DateTime(),
              'constraints' => [new NotBlank, new Date]
            ])
            ->add('notes', CollectionType::class , [
              'entry_type' => PointsType::class //Utilisation d'une collection de formulaire pour saisir les valeurs des notes (les formulaires portent sur les entités points
                                                //passées en paramètre du formulaire)
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $data = $form->getData(); //Récupération des données du formulaire
            $evaluation->setNom($data["nom"]); // Définition du nom de l'évaluation
            $evaluation->setDate($data["date"]); // -------- de la date -----------
            $evaluation->setEnseignant($this->getUser());
            $evaluation->setNotesSaisies(true);
            $entityManager->persist($evaluation);
            $entityManager->persist($partie);
            foreach ($partie->getNotes() as $note) {
              //Si la note dépasse le barême de la partie, on réduit la note à la valeur du barême
              if ($note->getValeur() > $partie->getBareme()) {
                $note->setValeur($partie->getBareme());
              }
              if ($note->getValeur() < -1) { // On teste si une valeur inférieure à -1 est rentrée pour ramener la note à 0. -1 est autorisé pour remarquer les absents
                  $note->setValeur(0);
              }
              $entityManager->persist($note);
            }
            $entityManager->flush();
            if($this->getUser()->getId() == $evaluation->getEnseignant()->getId()) {
                return $this->redirectToRoute('evaluation_enseignant',['id' => $this->getUser()->getId()]);
            }
            else {
                return $this->redirectToRoute('evaluation_autres',['id' => $this->getUser()->getId()]);
            }
        }
        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'parties' => $partie,
            'etudiants' => $evaluation->getGroupe()->getEtudiants()
        ]);
    }

    /**
     * @Route("/nouvelle-avec-parties/{slug}", name="evaluation_avec_parties_new", methods={"GET","POST"})
     */
    public function newAvecParties(Request $request, GroupeEtudiant $groupeConcerne): Response
    {
        //Récupération des informations de l'évaluation
        $formEval = $this->createFormBuilder()
            ->add('nom', TextType::class,[
                'constraints' => [
                    new NotBlank,
                    new Length(['max' => 255]),
                    new Regex(['pattern' => '/[a-zA-Z0-9]/', 'message' => 'Le nom de l\'évaluation doit contenir au moins un chiffre ou une lettre'])
                ],
            ])
            ->add('date', DateType::class, [
                'widget' => 'choice',
                'years' => range(date("Y")-5, date("Y")+1),
                'data' => new \DateTime(),
                'constraints' => [new NotBlank, new Date]
            ])
            ->getForm()
        ;
        $formEval->handleRequest($request);
        if($formEval->isSubmitted() && $formEval->isValid()) {
            $data = $formEval->getData(); //Récupération des données du formulaire
            //Mise en session des données de l'évaluation pour la créer à l'action suivante
            $request->getSession()->set('nomEval',$data["nom"]);
            $request->getSession()->set('dateEval', $data["date"]);
            $request->getSession()->set('idGroupeEval',$groupeConcerne->getId());
            $arbreInitial = [ // tableau qui sera utilisé pour initialiser la création des parties à la page suivante
                'id' => 1,
                'text' => $data["nom"],
                'nom' => $data["nom"],
                'bareme' => 20,
                'state' => ['expanded' => true],
                'tags' => ['/20']
            ];
            $request->getSession()->set('arbre_json',$arbreInitial); // Pour récupérer le tableau lors du chargement de la vue de l'action suivante
            return $this->redirectToRoute("creation_parties_eval");
        }
        return $this->render('evaluation_parties/saisie_info_eval_par_parties.html.twig', [
            'form' => $formEval->createView(),
        ]);
    }

    /**
     * @Route("/creation-parties", name="creation_parties_eval", methods={"GET","POST"})
     */
    public function creationParties(Request $request, GroupeEtudiantRepository $repo): Response
    {
        $form = $this->createFormBuilder()
            ->add('arbre', HiddenType::class) // Pour pouvoir stocker le tableau des parties et le récupérer lors de la validation
            ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();
            $data = $form->getData();
            $arbrePartiesRecupere = json_decode(urldecode($data['arbre'])); //Récupération des parties créées par l'utilisateur
            $tableauParties = []; //Initialisation du tableau qui contiendra les parties
            $evaluation = new Evaluation();
            $evaluation->setNom($request->getSession()->get("nomEval"));
            $evaluation->setDate($request->getSession()->get("dateEval"));
            $evaluation->setGroupe($repo->findOneById($request->getSession()->get("idGroupeEval"))); //On refait le lien avec le groupe sinon le manager essaye de le persist lui aussi comme si c'était une nouvelle entité
            $evaluation->setEnseignant($this->getUser());
            $evaluation->setNotesSaisies(false);
            $entityManager->persist($evaluation);
            //récupération des objets Partie depuis l'arborescence créée dans le JSON et mise en base de données
            $this->definirPartiesDepuisTableauJS($evaluation, $arbrePartiesRecupere[0], $tableauParties);
            $tableauParties[0]->setIntitule("Évaluation");
            foreach ($tableauParties as $partie) {
                $entityManager->persist($partie);
            }
            //Creation des entités points correspondant à l'évaluation et toutes ses parties et mise en base de données
            foreach ($evaluation->getGroupe()->getEtudiants() as $etudiant) {
                foreach ($tableauParties as $partie) {
                    $note = new Points();
                    $note->setEtudiant($etudiant);
                    $note->setPartie($partie);
                    $note->setValeur(0);
                    $entityManager->persist($note);
                }
            }
            $entityManager->flush();
            return $this->redirectToRoute('evaluation_edit', [
                'slug' => $evaluation->getSlug()
            ]);
        }
        return $this->render('evaluation_parties/creation_arborescence_parties.html.twig', [
            'form' => $form->createView()
        ]);
    }

    //Cette fonction permet, à partir du tableau récupéré de la vue de création des parties, de remplir un tableau d'objets parties exploitable par la suite
    public function definirPartiesDepuisTableauJS($evaluation, $partieCourante, &$tableauARemplir, $partieParent = null) {
        $partie = new Partie();
        if ($partie->getIntitule() == $evaluation->getNom()) {
            $partie->setIntitule("Évaluation");
        }
        else {
            $partie->setIntitule($partieCourante->nom);
        }
        $partie->setBareme($partieCourante->bareme);
        $partie->setEvaluation($evaluation);
        $partie->setParent($partieParent);
        $tableauARemplir[] = $partie;
        if(isset($partieCourante->nodes)) {
            foreach ($partieCourante->nodes as $enfant) {
                $this->definirPartiesDepuisTableauJS($evaluation, $enfant, $tableauARemplir, $partie);
            }
        }
    }

    /**
     * @Route("/consulter/{slug}", name="evaluation_show", methods={"GET"})
     */
    public function show(Evaluation $evaluation, PointsRepository $repoPoints): Response
    {
        $notes = $repoPoints->findAllByEvaluation($evaluation->getId());
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'notes' => $notes
        ]);
    }

    /**
     * @Route("/modifier/{slug}", name="evaluation_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evaluation $evaluation, PartieRepository $repoPartie, PointsRepository $repoPoints): Response
    {
      $this->denyAccessUnlessGranted('EVALUATION_EDIT', $evaluation);
      $partiesASaisir = $repoPartie->findLowestPartiesByEvaluationIdWithGrades($evaluation->getId());
      $notes = $repoPoints->findAllFromLowestParties($evaluation->getId());
      $form = $this->createFormBuilder(['notes' => $notes])
          ->add('nom', TextType::class, [
            'data' => $evaluation->getNom(),
            'constraints' => [
                new Regex(['pattern' => '/[a-zA-Z0-9]/', 'message' => 'Le nom de l\'évaluation doit contenir au moins un chiffre ou une lettre']),
                new NotBlank(),
                new Length(['max' => 255]),
            ]
          ])
          ->add('date', DateType::class, [
            'widget' => 'choice',
            'years' => range(date("Y")-5, date("Y")+1),
            'data' => $evaluation->getDate(),
            'constraints' => [new NotBlank, new Date]
          ])
          ->add('notes', CollectionType::class , [
              'entry_type' => PointsType::class, //Utilisation d'une collection de formulaire pour saisir les valeurs des notes (les formulaires portent sur les entités points
              //passées en paramètre du formulaire)
          ])
          ->getForm();
      $form->handleRequest($request);
      if ($form->isSubmitted()  && $form->isValid()) {
          $entityManager = $this->getDoctrine()->getManager();
          $data = $form->getData();
          $evaluation->setNom($data["nom"]);
          $evaluation->setDate($data["date"]);
          $evaluation->setNotesSaisies(true);
          $entityManager->persist($evaluation);
          $notes = $data['notes'];
          foreach ($notes as $note) {
              $entityManager->persist($note);
          }
          //Calcul des notes supérieures
          //On récupère les parties dont la note n'a pas été calculée (celles qui ont au moins une sous-partie). Les parties sont organisées des plus basses aux plus hautes
          $partiesACalculer = $repoPartie->findHighestByEvaluation($evaluation->getId());
          foreach ($evaluation->getGroupe()->getEtudiants() as $etudiant) {
              foreach ($partiesACalculer as $partie) {
                  $sommePtsSousPartie = 0;
                  $sousParties = $partie->getChildren();
                  $etudiantAbsent = true; //On suppose que l'étudiant est absent à cette partie sauf si on trouve une note supérieure à -1 dans les sous parties (il peut avoir manqué seulement une partie de l'évaluation ainsi)
                  //On fait la somme des notes obtenues aux sous parties
                  foreach ($sousParties as $sousPartie ) {
                      $point = $repoPoints->findByPartieAndByStudent($sousPartie->getId(), $etudiant->getId());
                      //On ne prend pas en compte -1 dans le calcul total
                      if ($point->getValeur() >= 0) {
                          $sommePtsSousPartie += $point->getValeur();
                          $etudiantAbsent = false;
                      }
                  }
                  $point = $repoPoints->findByPartieAndByStudent($partie->getId(), $etudiant->getId());
                  //Si la note est inférieure à 0 c'est que l'étudiant était absent
                  if($etudiantAbsent) {
                      $point->setValeur(-1);
                  }
                  else {
                      $point->setValeur($sommePtsSousPartie);
                  }
                  $entityManager->persist($point);
              }
          }
          $entityManager->flush();
          return $this->redirectToRoute('evaluation_enseignant');
      }
      return $this->render('evaluation/edit.html.twig', [
          'evaluation' => $evaluation,
          'form' => $form->createView(),
          'parties' => $partiesASaisir,
          'etudiants' => $evaluation->getGroupe()->getEtudiants(),
      ]);
    }

    /**
     * @Route("/supprimer/{slug}", name="evaluation_delete", methods={"GET"})
     */
    public function delete(Request $request, Evaluation $evaluation): Response
    {
        $this->denyAccessUnlessGranted('EVALUATION_DELETE', $evaluation);
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
        if($this->getUser()->getId() == $evaluation->getEnseignant()->getId()) {
            return $this->redirectToRoute('evaluation_enseignant',['id' => $this->getUser()->getId()]);
        }
        else {
            return $this->redirectToRoute('evaluation_autres',['id' => $this->getUser()->getId()]);
        }
    }

    /**
     * @Route("/{typeEval}/choisir-groupe/", name="evaluation_choose_group")
     */
    public function choixGroupeEval(Request $request, GroupeEtudiantRepository $repoGroupe, $typeEval): Response
    {
      $groupes = $repoGroupe->findAllWithoutNonEvaluableGroups();
      $form = $this->createFormBuilder()
          ->add('groupes', EntityType::class, [
            'constraints' => [new NotNull],
            'class' => GroupeEtudiant::Class,
            'choice_label' => false,
            'label' => false,
            'mapped' => false,
            'expanded' => true,
            'multiple' => false,
            'choices' => $groupes
          ])
          ->getForm();
      $form->handleRequest($request);
      if ($form->isSubmitted()  && $form->isValid()) {
        //En fonction du type d'évaluation correct on renvoie sur la bonne route avec le groupe choisi
        if (strcmp($typeEval, "simple") == 0 ) {
            return $this->redirectToRoute('evaluation_new',['slug' => $form->get("groupes")->getData()->getSlug()]);
        }
        else {
            if (strcmp($typeEval, "avec-parties") == 0 ) {
                return $this->redirectToRoute('evaluation_avec_parties_new',['slug' => $form->get("groupes")->getData()->getSlug()]);
            }
        }
      }
      return $this->render('evaluation/choix_groupe.html.twig', ['groupes' => $groupes,'form' => $form->createView()]);
    }



    /**
     * @Route("/choisir-groupes-plusieurs-evals", name="evaluations_choose_groups")
     */
    public function chooseGroupsEvals(Request $request, EvaluationRepository $repoEval, GroupeEtudiantRepository $repoGroupe): Response
    {
      $evals = $repoEval->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser());
      $tousLesGroupes = $repoGroupe->findAllWithoutNonEvaluableGroups();

      $groupes = array();

      foreach ($evals as $eval) // Parcours des évaluations de l'utilisateur
      {
        if (!in_array($eval->getGroupe(), $groupes))
        {
          array_push($groupes, $eval->getGroupe());
        }
      }

      $evals = $repoEval->findOtherEvaluationsWithGradesAndCreatorAndGroup($this->getUser());

      foreach ($evals as $eval) // Parcours des autres évaluations
      {
        if (!in_array($eval->getGroupe(), $groupes))
        {
          array_push($groupes, $eval->getGroupe());
        }
      }

      $form = $this->createFormBuilder()
        ->add('groupes', EntityType::class, [
          'class' => GroupeEtudiant::Class, //On veut choisir des groupes
          'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
          'label' => false, // On n'affiche pas le label du champ
          'expanded' => true, // Pour avoir des cases
          'multiple' => false, // à cocher
          'choices' => $groupes // On choisira parmis les groupes de plus haut niveau évaluables qui ont au moins 1 évaluation que les concernent
        ])
        ->getForm();

      $form->handleRequest($request);

      if ($form->isSubmitted()  && $form->isValid()) 
      {
        $leGroupeChoisi = new GroupeEtudiant();
        
        foreach ($tousLesGroupes as $groupe)
        {
          if ($groupe == $form->get("groupes")->getData())
          {
            $leGroupeChoisi = $groupe;
          }
        }

        return $this->redirectToRoute('evaluations_choose_subgroups', ['slug' => $leGroupeChoisi->getSlug()]);
      }

      return $this->render('evaluation/choix_groupes_plusieurs_evals.html.twig', ['groupes' => $groupes,'form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-sous-groupes-plusieurs-evals/{slug}", name="evaluations_choose_subgroups")
     */
    public function chooseSubGroupsEvals(Request $request, $slug, GroupeEtudiantRepository $repoGroupe): Response
    {
      $groupePrincipal = $repoGroupe->findOneBySlug($slug);

      $sousGroupes = $repoGroupe->findAllOrderedFromNode($groupePrincipal);

      unset($sousGroupes[0]); // Pour eviter de réafficher le groupe principal

      $form = $this->createFormBuilder()
      ->add('sousGroupes', EntityType::class, [
        'class' => GroupeEtudiant::Class, //On veut choisir des groupes
        'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
        'label' => false, // On n'affiche pas le label du champ
        'expanded' => true, // Pour avoir des cases
        'multiple' => true, // à cocher
        'choices' => $sousGroupes // On choisira parmis les sous groupes du groupe choisi au préalable
      ])
      ->getForm();

      $form->handleRequest($request);

      if ($form->isSubmitted()  && $form->isValid()) 
      {
        $sousGroupes = $form->get('sousGroupes')->getData();
        $_SESSION['sousGroupes'] = $sousGroupes;
        
        return $this->redirectToRoute('evaluations_groups_choose_evals',
        ['slugGroupe' => $groupePrincipal->getSlug()]);
      }

      return $this->render('evaluation/choix_sous-groupes_plusieurs_evals.html.twig', 
      ['groupe' => $groupePrincipal, 
      'sousGroupes' => $sousGroupes,
      'form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-evals-plusieurs-evals-groupes/{slugGroupe}", name="evaluations_groups_choose_evals")
     */
    public function chooseEvalsGroupsEvals(Request $request, $slugGroupe, GroupeEtudiantRepository $repoGroupe, EvaluationRepository $repoEval): Response
    {
      $groupePrincipal = $repoGroupe->findOneBySlug($slugGroupe);
      $toutesLesEvals = $repoEval->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser());
      $evals = array();

      foreach ($toutesLesEvals as $eval)
      {
        if ($eval->getGroupe() == $groupePrincipal)
        {
          array_push($evals, $eval);
        }
      }

      $toutesLesEvals = $repoEval->findOtherEvaluationsWithGradesAndCreatorAndGroup($this->getUser());
      
      foreach ($toutesLesEvals as $eval)
      {
        if ($eval->getGroupe() == $groupePrincipal)
        {
          array_push($evals, $eval);
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
        
        return $this->redirectToRoute('evaluations_groups_choose_stats_type',
        ['slugGroupe' => $slugGroupe]);
      }
      
      return $this->render('evaluation/choix_evals_plusieurs_evals_groupes.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/choisir-type-stats-plusieurs-evals-groupe/{slugGroupe}", name="evaluations_groups_choose_stats_type")
     */
    public function chooseStatsGroupsEvals(Request $request, $slugGroupe, GroupeEtudiantRepository $repoGroupe, PointsRepository $repoPoints): Response
    {
      $groupePrincipal = $repoGroupe->findOneBySlug($slugGroupe);

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
            $listeStatsParGroupe = array(); // On initialise un tableau vide qui contiendra les statistiques des groupes choisis

            $lesGroupes = array(); // On regroupe le groupe principal et les sous groupes pour faciliter la requete

            array_push($lesGroupes, $groupePrincipal);

            foreach ($_SESSION['sousGroupes'] as $sousGroupe)
            {
              array_push($lesGroupes, $sousGroupe);
            }

            foreach ($lesGroupes as $groupe) // On récupère les notes du groupe principal et des sous groupes sur toutes les évaluations choisis
            {
              $tabPoints = array();
              foreach ($_SESSION['evalsChoisi'] as $eval) 
              {
                array_push($tabPoints, $repoPoints->findByGroupe($eval->getSlug(), $groupe->getSlug()));
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
              "notes" => $this->repartition($copieTabPoints),
              "allNotes" => $copieTabPoints,
              "moyenne" => $this->moyenne($copieTabPoints),
              "ecartType" => $this->ecartType($copieTabPoints),
              "minimum" => $this->minimum($copieTabPoints),
              "maximum" => $this->maximum($copieTabPoints),
              "mediane" => $this->mediane($copieTabPoints)
              );
              
            }
            
            // Mise en session des stats
            $_SESSION['stats'] = $listeStatsParGroupe;


            return $this->render('evaluation/stats_plusieurs_evals_groupes.html.twig', [
              'groupes' => $listeStatsParGroupe,
              'evaluations' => $_SESSION['evalsChoisi'],
              'index' => 1]); // L'index indiquant quel type de statistiques à été choisi
          /*}
          else if ($form->get("stats")->getData() == 2)
          {
            //évolution des résultats
            
          }
          else
          {
            //les 2
            
          }*/
        
        }
        return $this->render('evaluation/choix_stats_plusieurs_evals_groupes.html.twig', ['form' => $form->createView()]);

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
