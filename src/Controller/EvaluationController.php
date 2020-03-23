<?php

namespace App\Controller;

use App\Form\PointsType;
use App\Form\EvaluationType;
use App\Entity\Evaluation;
use App\Entity\Etudiant;
use App\Entity\Partie;
use App\Entity\Statut;
use App\Entity\Points;
use App\Entity\Enseignant;
use App\Entity\GroupeEtudiant;
use App\Repository\StatutRepository;
use App\Repository\PointsRepository;
use App\Repository\EvaluationRepository;
use App\Repository\GroupeEtudiantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/evaluation")
 */
class EvaluationController extends AbstractController
{
    /**
     * @Route("/mes-evaluations", name="evaluation_enseignant", methods={"GET"})
     */
    public function indexEnseignant(EvaluationRepository $evaluationRepository): Response
    {
        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluationRepository->findMyEvaluationsWithGradesAndCreatorAndGroup($this->getUser()),
            'self' => 'active',
            'other' => ''
        ]);
    }

    /**
     * @Route("/previsualisation-mail/{slug}", name="previsualisation_mail", methods={"GET"})
     */
    public function previsualisationMail(Evaluation $evaluation): Response
    {
      $this->getUser()->checkAdminOrAuthorized($evaluation->getEnseignant());

      return $this->render('evaluation/previsualisationMail.html.twig',[
        'evaluation' => $evaluation
      ]);
    }

    /**
     * @Route("/exemple-mail/{id}", name="exemple_mail", methods={"GET"})
     */
     public function exempleMail(Request $request, EvaluationRepository $evaluationRepository, Evaluation $evaluation, PointsRepository $pointsRepository): Response
     {
         $this->getUser()->checkAdminOrAuthorized($evaluation->getEnseignant());

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

         return $this->render('evaluation/mailEnvoye.html.twig',[
           'etudiantsEtNotes' => $notesEtudiants[0],
           'stats' => $stats,
           'position' => $position,
           'effectif' => $effectif
         ]);
       }

    /**
     * @Route("/envoi-mail/{slug}", name="envoi_mail", methods={"GET"})
     */
    public function envoiMail(Request $request, EvaluationRepository $evaluationRepository, Evaluation $evaluation, PointsRepository $pointsRepository, \Swift_Mailer $mailer): Response
    {
        $this->getUser()->checkAdminOrAuthorized($evaluation->getEnseignant());

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

        for ($i=0; $i < count($notesEtudiants); $i++) {
          $noteEtudiant = $notesEtudiants[$i]->getValeur();
          $position = array_search($noteEtudiant, $copieTabRang) + 1;

          $message = (new \Swift_Message('Noteo - ' . $evaluation->getNom()))
          ->setFrom('contact@noteo.me')
          ->setTo($notesEtudiants[$i]->getEtudiant()->getMail())
          ->setBody(
              $this->renderView('evaluation/mailEnvoye.html.twig',[
                'etudiantsEtNotes' => $notesEtudiants[$i],
                'stats' => $stats,
                'position' => $position,
                'effectif' => $effectif
          ]),'text/html');

          $mailer->send($message);
        }

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
            'self' => '',
            'other' => 'active'
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
            ->add('nom', TextType::class)
            ->add('date', DateType::class, [
              'widget' => 'single_text'
            ])
            ->add('notes', CollectionType::class , [
              'entry_type' => PointsType::class //Utilisation d'une collection de formulaire pour saisir les valeurs des notes (les formulaires portent sur les entités points
                                                //passées en paramètre du formulaire)
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

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
              if ($note->getValeur() < 0) {
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

    public function validerEntite ($entite, $validator) {

      //Utilisation de la méthode validate du validator pour valider l'entité selon les regles définies dans celle ci
      $errors = $validator->validate($entite);

      //Si erreur, retour d'un objet Response qui affichera les erreurs
      if (count($errors) > 0) {
          $errorsString = (string) $errors;
          return new Response($errorsString);
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
      $this->getUser()->checkAdminOrAuthorized($evaluation->getEnseignant());

      ////////////POUR COMMENTAIRES VOIR METHODE NEW////////////
      foreach ($evaluation->getParties() as $partie) {
        $tab = $partie->getNotes();
      }

      $form = $this->createFormBuilder(['notes' => $tab])
          ->add('nom', TextType::class, [
            'data' => $evaluation->getNom()
          ])
          ->add('date', DateType::class, [
            'widget' => 'single_text',
            'data' => $evaluation->getDateUnformatted(),
          ])
          ->add('notes', CollectionType::class , [
            'entry_type' => PointsType::class
          ])
          ->getForm();

      $form->handleRequest($request);

      if ($form->isSubmitted()) {

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
            if ($note->getValeur() < 0) {
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
        $this->getUser()->checkAdminOrAuthorized($evaluation->getEnseignant());

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

      if ($form->isSubmitted()) {

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
              'choices' => $repoStatut->findAll() // On choisira parmis tous les statut
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

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
