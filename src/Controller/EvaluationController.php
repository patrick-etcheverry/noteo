<?php

namespace App\Controller;

use App\Form\PointsType;
use App\Entity\Evaluation;
use App\Entity\Partie;
use App\Entity\Statut;
use App\Entity\Points;
use App\Entity\GroupeEtudiant;
use App\Repository\PartieRepository;
use App\Repository\StatutRepository;
use App\Repository\PointsRepository;
use App\Repository\EvaluationRepository;
use App\Repository\GroupeEtudiantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
     * @Route("/previsualisation-mail/{slug}", name="previsualisation_mail", methods={"GET"})
     */
    public function previsualisationMail(Evaluation $evaluation, PointsRepository $pointsRepository): Response
    {
      $nbEtudiants = count($pointsRepository->findNotesAndEtudiantByEvaluation($evaluation));

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
     public function exempleMail(Request $request, EvaluationRepository $evaluationRepository, Evaluation $evaluation, PointsRepository $pointsRepository): Response
     {
         $this->denyAccessUnlessGranted('EVALUATION_EXEMPLE_MAIL', $evaluation);

         // Récupération de la session
         $session = $request->getSession();
         // Récupération des stats mises en session
         $stats = $session->get('stats');

         $notesEtudiants = $pointsRepository->findNotesAndEtudiantByEvaluation($evaluation);

         $tabRang = $pointsRepository->findUniqueByGroupe($evaluation->getId(),$evaluation->getGroupe()->getId());
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
    public function envoiMail(Request $request, EvaluationRepository $evaluationRepository, Evaluation $evaluation, PointsRepository $pointsRepository, \Swift_Mailer $mailer): Response
    {
        $this->denyAccessUnlessGranted('EVALUATION_ENVOI_MAIL', $evaluation);

        // Récupération de la session
        $session = $request->getSession();
        // Récupération des stats mises en session
        $stats = $session->get('stats');

        $notesEtudiants = $pointsRepository->findNotesAndEtudiantByEvaluation($evaluation);

        $tabRang = $pointsRepository->findUniqueByGroupe($evaluation->getId(),$evaluation->getGroupe()->getId());
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

        return $this->render('evaluation/stats.html.twig', [
            'groupes' => $stats,
            'evaluation' => $evaluation
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
    public function new(Request $request, GroupeEtudiant $groupeConcerne, ValidatorInterface $validator): Response
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
              'widget' => 'single_text',
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

            //Validation de l'entité hydratée à partir des données du formulaire
            $this->validerEntite($evaluation, $validator);
            $this->validerEntite($partie, $validator);

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
              //On valide l'entité note hydratée avec la collection de formulaires
              $this->validerEntite($note, $validator);
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
                'widget' => 'single_text',
                'constraints' => [new NotBlank, new Date]
            ])
            ->getForm()
        ;
        $formEval->handleRequest($request);

        if($formEval->isSubmitted() && $formEval->isValid()) {
            $data = $formEval->getData(); //Récupération des données du formulaire
            $evaluation = new Evaluation();
            $evaluation->setNom($data["nom"]);
            $evaluation->setDate($data["date"]);
            $evaluation->setGroupe($groupeConcerne);

            $arbreInitial = [ // tableau qui sera utilisé pour initialiser la création des parties à la page suivante
                'id' => 1,
                'text' => $data["nom"],
                'nom' => $data["nom"],
                'bareme' => 20,
                'state' => ['expanded' => true],
                'tags' => ['/20']
            ];

            $request->getSession()->set('evaluation',$evaluation); // Mise en session de l'objet évaluation créé pour le persister à la fonction suivante une fois les parties créées
            $request->getSession()->set('arbre_json',$arbreInitial); // Pour récupérer le tableau lors du chargement de la vue suivante
            return $this->redirectToRoute("creation_parties_eval");
        }
        return $this->render('evaluation/saisie_info_eval_par_parties.html.twig', [
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
            $data = $form->getData();
            $arbrePartiesRecupere = json_decode(urldecode($data['arbre'])); //Récupération des parties créées par l'utilisateur
            $tableauParties = []; //Initialisation du tableau qui contiendra les parties
            //récupération des objets Partie depuis l'arborescence créée dans le JSON
            $this->definirPartiesDepuisTableauJS($request->getSession()->get('evaluation'), $arbrePartiesRecupere[0], $tableauParties);
            $entityManager = $this->getDoctrine()->getManager();
            $evaluation = $request->getSession()->get('evaluation');
            $evaluation->setGroupe($repo->findOneById($evaluation->getGroupe())); //On refait le lien avec le groupe sinon le manager essaye de le persist lui aussi comme si c'était une nouvelle entité
            $evaluation->setEnseignant($this->getUser());
            $entityManager->persist($evaluation);
            foreach ($tableauParties as $partie) {
                $entityManager->persist($partie);
            }
            //Creation des entités points correspondant à l'évaluation et toutes ses parties
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
            return $this->redirectToRoute('saisie_notes_parties_eval');
        }
        return $this->render('evaluation/creation_arborescence_parties.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/saisie-notes-parties", name="saisie_notes_parties_eval", methods={"GET","POST"})
     */
    public function saisieNotesParties(Request $request, EvaluationRepository $repoEval, PartieRepository $repoPartie, PointsRepository $repoPoints): Response
    {
        //Récupération de l'évaluation créée au départ et des parties créées précédemment
        $evaluation = $repoEval->findEvaluationWithGroupAndStudents($request->getSession()->get('evaluation')->getId());
        $partiesASaisir = $repoPartie->findLowestPartiesByEvaluationIdWithGrades($evaluation->getId());
        $notes = $repoPoints->findAllFromLowestParties($evaluation->getId());

        //Création du formulaire de saisie des points
        $form = $this->createFormBuilder(["notes" => $notes])
            ->add('notes', CollectionType::class , [
                'entry_type' => PointsType::class //Utilisation d'une collection de formulaire pour saisir les valeurs des notes (les formulaires portent sur les entités points
                                                  //passées en paramètre du formulaire)
            ])
            ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted()) {
            //récupération des données
            $data = $form->getData();
            $notes = $data['notes'];
            $evaluation = $request->getSession()->get('evaluation');
            $parties = $request->getSession()->get('parties');
            $entityManager = $this->getDoctrine()->getManager();
            //Pour que le manager puisse les créer en base de données, on va devoir repréciser les liens entre les entités créées précédemment (parties, points, étudiants, évaluation, enseignant).
            //En l'état actuel le manager va penser que toutes les entités sont à persister. Par exemple evaluation->getEnseignant() : Le manager va essayer de persister un nouvel enseignant avec
            //cette entité ce qui va causer une erreur car elle exister déjà
            //Liens pour évaluation
            $evaluation->setEnseignant($this->getUser());
            $evaluation->setGroupe($repoGroupe->findOneById($evaluation->getGroupe()->getId()));
            $entityManager->persist($evaluation);

            //Persistence des parties
            foreach ($parties as $partie) {
                $partie->setEvaluation($evaluation);
                $entityManager->persist($partie);
            }

            //On replace la partie représentant l'évaluation au début du tableau des parties
            $partieEvaluation = array_pop($parties);
            array_unshift($parties, $partieEvaluation);

            //On va devoir recréer les liens entre les entités points et les parties et étudiant. On parcours alors les entités points avec un intervalle égal au nombre de parties.
            //Si x est le nombre de parties, on sait que les xèmes premières correspondent à un étudiant, les x suivantes à un autre, et ainsi de suite
            $intervalle =count($parties);
            $nbEtudiants = count($evaluation->getGroupe()->getEtudiants());
            //Initialisiation du parcours
            $premierIndexaTraiter = 0;
            $dernierIndexATraiter = $intervalle -1;
            //Parcours
            for($i = 0; $i < $nbEtudiants ; $i++ ) {
                $partieCourante = 0;
                for($j = $premierIndexaTraiter; $j <= $dernierIndexATraiter ; $j++ ) {
                    $notes[$j]->setEtudiant($evaluation->getGroupe()->getEtudiants()[$i]);
                    $notes[$j]->setPartie($parties[$partieCourante]);
                    $entityManager->persist($notes[$j]);
                    $partieCourante++;
                }
                //Préparation du tout suivant
                $premierIndexaTraiter = $dernierIndexATraiter + 1;
                $dernierIndexATraiter = $dernierIndexATraiter + $intervalle;
            }
            //Validation des modifications et libération de la place en mémoire des variables
            $entityManager->flush();
            return $this->redirectToRoute('evaluation_enseignant');
        }
        return $this->render('evaluation/saisie_notes_parties.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'parties' => $partiesASaisir,
            'etudiants' => $evaluation->getGroupe()->getEtudiants()
        ]);
    }

    public function validerEntite ($entite, $validator) {
      //Utilisation de la méthode validate du validator pour valider l'entité selon les regles définies dans celle ci
      $errors = $validator->validate($entite);
      //Si erreur, retour d'un objet Response qui affichera les erreurs
      if (count($errors) > 0) {
          $errorsString = (string) $errors;
          return new Response($errorsString);
      }
    }

    //Cette fonction permet, à partir du tableau récupéré de la vue de création des parties, de remplir un tableau d'objets parties exploitable par la suite
    public function definirPartiesDepuisTableauJS($evaluation, $partieCourante, &$tableauARemplir, $partieParent = null) {
        $partie = new Partie();
        $partie->setIntitule($partieCourante->nom);
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
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    /**
     * @Route("/modifier/{slug}", name="evaluation_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Evaluation $evaluation, ValidatorInterface $validator): Response
    {
      $this->denyAccessUnlessGranted('EVALUATION_EDIT', $evaluation);

      ////////////POUR COMMENTAIRES VOIR METHODE NEW////////////
      foreach ($evaluation->getParties() as $partie) {
        $tab = $partie->getNotes();
      }

      $form = $this->createFormBuilder(['notes' => $tab])
          ->add('nom', TextType::class, [
            'data' => $evaluation->getNom(),
            'constraints' => [
                new Regex(['pattern' => '/[a-zA-Z0-9]/', 'message' => 'Le nom de l\'évaluation doit contenir au moins un chiffre ou une lettre']),
                new NotBlank(),
                new Length(['max' => 255]),
            ]
          ])
          ->add('date', DateType::class, [
            'widget' => 'single_text',
            'data' => $evaluation->getDateUnformatted(),
            'constraints' => [new NotBlank, new Date]
          ])
          ->add('notes', CollectionType::class , [
            'entry_type' => PointsType::class
          ])
          ->getForm();

      $form->handleRequest($request);

      if ($form->isSubmitted()  && $form->isValid()) {

          $entityManager = $this->getDoctrine()->getManager();

          $data = $form->getData();

          $evaluation->setNom($data["nom"]);
          $evaluation->setDate($data["date"]);

          $this->validerEntite($evaluation, $validator);

          $entityManager->persist($evaluation);
          $entityManager->persist($partie);

          foreach ($partie->getNotes() as $note) {
            if ($note->getValeur() > $partie->getBareme()) {
              $note->setValeur($partie->getBareme());
            }
            if ($note->getValeur() < -1) {
                $note->setValeur(0);
            }
            $this->validerEntite($note, $validator);
            $entityManager->persist($note);
          }

          $entityManager->flush();


              return $this->redirectToRoute('evaluation_show',[
                'slug' => $evaluation->getSlug()
              ]);

      }

      return $this->render('evaluation/edit.html.twig', [
          'evaluation' => $evaluation,
          'form' => $form->createView()
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
     * @Route("/choisir-groupe", name="evaluation_choose_group")
     */
    public function chooseGroup(Request $request, GroupeEtudiantRepository $repoGroupe): Response
    {
      $groupes = $repoGroupe->findAllWithoutNonEvaluableGroups();

      $form = $this->createFormBuilder()
          ->add('groupes', EntityType::class, [
            'constraints' => [new NotNull],
            'class' => GroupeEtudiant::Class, //On veut choisir des groupes
            'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
            'label' => false, // On n'affiche pas le label du champ
            'mapped' => false, // Pour que l'attribut ne soit pas immédiatement mis en BD mais soit récupérable après soumission du formulaire
            'expanded' => true, // Pour avoir des boutons
            'multiple' => false, // radios
            'choices' => $groupes
          ])
          ->getForm();

      $form->handleRequest($request);

      if ($form->isSubmitted()  && $form->isValid()) {

        return $this->redirectToRoute('evaluation_new',['slug' => $form->get("groupes")->getData()->getSlug()]);

      }

      return $this->render('evaluation/choix_groupe.html.twig', ['groupes' => $groupes,'form' => $form->createView()]);
    }

    /**
     * @Route("/{slugEval}/choisir-groupes-et-statuts/{slugGroupe}", name="evaluation_choose_groups", methods={"GET","POST"})
     */
    public function chooseGroups(Request $request, $slugEval, $slugGroupe, StatutRepository $repoStatut, EvaluationRepository $repoEval, GroupeEtudiantRepository $repoGroupe, PointsRepository $repoPoints ): Response
    {
        // Récupération de la session
        $session = $request->getSession();

        //On récupere l'évaluation que l'on traite pour afficher ses informations générales dans les statistiques
        $evaluation = $repoEval->findOneBySlug($slugEval);

        //On récupère le groupe concerné par l'évaluation
        $groupeConcerne = $repoGroupe->findOneBySlug($slugGroupe);

        //On récupère la liste de tous les enfants (directs et indirects) du groupe concerné pour choisir ceux sur lesquels on veut des statistiques
        $choixGroupe = $repoGroupe->findAllOrderedFromNode($groupeConcerne);

        //Création du formulaire pour choisir les groupes / status sur lesquels on veut des statistiques
        $form = $this->createFormBuilder()
            ->add('groupes', EntityType::class, [
              'class' => GroupeEtudiant::Class, //On veut choisir des groupes
              'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités en utilisant les variables du champ
              'label' => false, // On n'affiche pas le label du champ
              'mapped' => false, // Pour que l'attribut ne soit pas immédiatement mis en BD mais soit récupérable après soumission du formulaire
              'expanded' => true, // Pour avoir des cases
              'multiple' => true, // à cocher
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

            $listeStatsParGroupe = array(); // On initialise un tableau vide qui contiendra les statistiques des groupes choisis

            $listeStatsParStatut = array(); // On initialise un tableau vide qui contiendra les statistiques des statuts choisis

            //Pour tous les groupes sélectionnés
            foreach ($form->get("groupes")->getData() as $groupe) {

                //On récupère toutes les notes du groupe courant
                $tabPoints = $repoPoints->findByGroupe($slugEval, $groupe->getSlug());

                //On crée une copie de tabPoints qui contiendra les valeurs des notes pour simplifier le tableau renvoyé par la requete
                $copieTabPoints = array();
                foreach ($tabPoints as $element) {
                    $copieTabPoints[] = $element["valeur"];
                }

                //On remplit le tableau qui contiendra toutes les statistiques du groupe
                $listeStatsParGroupe[] = array("nom" => $groupe->getNom(),
                                             "notes" => $this->repartition($copieTabPoints),
                                             "moyenne" => $this->moyenne($copieTabPoints),
                                             "ecartType" => $this->ecartType($copieTabPoints),
                                             "minimum" => $this->minimum($copieTabPoints),
                                             "maximum" => $this->maximum($copieTabPoints),
                                             "mediane" => $this->mediane($copieTabPoints)
                                             );
            }

            //Pour tous les statuts sélectionnés
            foreach ($form->get("statuts")->getData() as $statut) {

                $tabPoints = $repoPoints->findByStatut($slugEval, $statut->getSlug());

                $copieTabPoints = array();
                foreach ($tabPoints as $note) {
                    $copieTabPoints[] = $note["valeur"];
                }

                $listeStatsParStatut[] = array("nom" => $statut->getNom(),
                                               "notes" => $this->repartition($copieTabPoints),
                                               "moyenne" => $this->moyenne($copieTabPoints),
                                               "ecartType" => $this->ecartType($copieTabPoints),
                                               "minimum" => $this->minimum($copieTabPoints),
                                               "maximum" => $this->maximum($copieTabPoints),
                                               "mediane" => $this->mediane($copieTabPoints)
                                               );
            }

            $groupes = array_merge($listeStatsParGroupe, $listeStatsParStatut); // On fusionne les deux tableaux pour éviter le dédoublement des traitements dans la vue

            // Mise en session des stats
            $session->set('stats',$groupes);

            return $this->render('evaluation/stats.html.twig', [
                'groupes' => $groupes,
                'evaluation' => $evaluation
            ]);
        }

        return $this->render('evaluation/choix_groupes.html.twig', [
            'form' => $form->createView(),
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
}
