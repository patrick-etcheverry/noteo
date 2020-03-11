<?php

namespace App\Controller;

use App\Entity\GroupeEtudiant;
use App\Form\GroupeEtudiantType;
use App\Form\SousGroupeEtudiantType;
use App\Form\GroupeEtudiantEditType;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/groupes")
 */
class GroupeEtudiantController extends AbstractController
{
    /**
     * @Route("/", name="groupe_etudiant_index", methods={"GET"})
     */
    public function index(): Response
    {

      $this->getUser()->checkUser();


        // On prépare un tableau d'options qui servera à paramètrer l'affichage de notre arbre
        $options = array(
            'decorate' => true,
            'childOpen' => '<tr>',
            'childClose' => '</tr>',
            'nodeDecorator' => function($node) {

                  /* Le but de cette fonction est de déterminer comment sera affiché chaque élément (node) de l'arborescence
                  sachant que $node est un groupe, la fonction est appliquée à chaque élément qui sera passé en paramètre, et
                  elle est appliquée à tous les groupes de l'arborescence */

                  /* On prépare un répository pour effectuer quelques requetes pour des éléments qui ne sont pas contenus dans l'élement
                  $node passé en paramètre (nottament les attributs enseignant et étudiants) */
                  $repo = $this->getDoctrine()->getRepository(GroupeEtudiant::class);

                  /////NOM/////
                  $indentation = "";

                  //Cette boucle détermine l'indentation du groupe en fonction de son niveau de profondeur
                  for ($i=0; $i < $node['lvl'] ; $i++) {
                    $indentation .= "&emsp;"; // $emsp désigne un ajout de 4 caractères espace
                  }

                  $nom = "<td>" . $indentation . $node['nom'] . "</td>";

                  /////EFFECTIF/////
                  $effectif = "<td>" . count($node['etudiants']) . "</td>";

                  /////DESCRIPTION/////
                  $description =  "<td>" . $node['description'] . "</td>";

                  /////ENSEIGNANT/////
                  $enseignant = "<td>" . $node['enseignant']['prenom'] . " " . $node['enseignant']['nom'] . "</td>";

                  /////estEvaluable/////
                  if ($node['estEvaluable']) {
                    $evaluable = "<td> Oui </td>";
                  }
                  else {
                    $evaluable = "<td> Non </td>";
                  }

                  /////ACTIONS/////
                  /////Cette section affiche les boutons d'actions liés à chaque groupes

                    //Consulter
                    $url = $this->generateUrl('groupe_etudiant_show', [ 'id' => $node['id'] ]);
                    $show = " <a href='$url'><i class='icon-eye' data-toggle='tooltip' title='Consulter le groupe'></i></a>";

                    if ($this->getUser()->isAdmin()) {
                      //Créer un sous-groupe
                      $url = $this->generateUrl('groupe_etudiant_new_sousGroupe', [ 'id' => $node['id'] ]);
                      $sousGroupe = "<a href='$url'><i class='icon-plus' data-toggle='tooltip' title='Créer un sous groupe'></i></a>";
                    }
                    else {
                      $sousGroupe = NULL;
                    }


                    //Créer une évaluation (seulement disponible si le groupe est évaluable)
                    if ($node['estEvaluable']) {
                      $url = $this->generateUrl('evaluation_new', [ 'id' => $node['id'] ]);
                      $evalSimple = "<a href='$url'><i class='icon-eval-simple' data-toggle='tooltip' title='Créer une évaluation simple'></i></a>";
                      // $evalParParties = "<a href='#'><i class='icon-eval-composee'></i></a>";
                    }
                    else {
                      $evalSimple = NULL;
                    //  $evalParParties = "";
                    }

                    //Modifier
                    if ($this->getUser()->isAdmin()) {
                    $url = $this->generateUrl('groupe_etudiant_edit', [ 'id' => $node['id'] ]);
                    $edit = "<a href=" . $url .  "><i class='icon-pencil-1' data-toggle='tooltip' title='Modifier le groupe'></i></a>";
                    }
                    else {
                      $edit = NULL;
                    }

                    //Supprimer
                    if ($this->getUser()->isAdmin()) {
                    $url = $this->generateUrl('groupe_etudiant_delete', [ 'id' => $node['id'] ]);
                    $delete = "<a href='$url' onclick='EcritureModale(\"$url\")' data-toggle='modal'><i class='icon-trash' data-toggle='tooltip' title='Supprimer le groupe'></i></a>";
                    }
                    else {
                      $delete = NULL;
                    }
                    //Mise à la suite des actions en une seule chaîne
                    $actions = "<td>" . $show  . $sousGroupe . $edit /*. $evalParParties */. $delete . $evalSimple . "</td>";

                  //Mise à la suite du contenu de toutes les colonnes du tableau en une seule chaîne
                  return $nom . $effectif . $description . $enseignant . $evaluable . $actions;
            }
        );

        //Cette variable représente le groupe des étudiants non affectés à un groupe de haut niveau
        $GroupeEtudiantsNonAffectés = "Etudiants non affectés";

        /* Cette requette personnalisée nous permet de récupérer tous les groupes, dans l'ordre de la hierarchie,
        sans le groupe de étudiants non affectés */
        $query = $this->getDoctrine()->getManager()
          ->createQueryBuilder()
          ->select('ge, en, et')
          ->from('App\Entity\GroupeEtudiant', 'ge')
          ->join('ge.enseignant', 'en')
          ->leftjoin('ge.etudiants', 'et')
          ->orderBy('ge.root, ge.lft', 'ASC')
          ->where("ge.nom != '$GroupeEtudiantsNonAffectés'")
          ->getQuery()
          ;

        //On utilise la fonction buildtree qui va créer l'arbre à afficher avec la requete personnalisée et les options que l'on a choisies précédemment
        $htmlTree = $this->getDoctrine()->getRepository(GroupeEtudiant::class)->buildTree($query->getArrayResult(), $options);

        return $this->render('groupe_etudiant/index.html.twig', [
            'tree' => $htmlTree
        ]);
    }

    /**
     * @Route("/new", name="groupe_etudiant_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {

        $this->getUser()->checkAdmin();


        $groupeEtudiant = new GroupeEtudiant();
        $form = $this->createForm(GroupeEtudiantType::class, $groupeEtudiant);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($groupeEtudiant);

            // Récupération du fichier CSV
            $fichierCSV = $form['fichier']->getData();

            // Ouverture du fichier CSV
            $fichier = fopen($fichierCSV,"r");

            // Pour éviter la dernière ligne du fichier
            $nbLignes = count(file($fichierCSV));

            // Récupération première ligne du fichier
            $ligne = chop(fgets($fichier));

            // Vérification première ligne du fichier
            if($ligne == "NOM;PRENOM;MAIL") {
              for ($i=0; $i < $nbLignes - 1; $i++) {
                $ligne = fgets($fichier);
                $ligneDecoupee = explode(";",$ligne);

                // Création de l'étudiant
                $etudiant = new Etudiant();
                $etudiant->setPrenom($ligneDecoupee[0]);
                $etudiant->setNom($ligneDecoupee[1]);
                $etudiant->setMail($ligneDecoupee[2]);
                $etudiant->setEstDemissionaire(false);
                // Ajout de l'étudiant au groupe
                $etudiant->addGroupe($groupeEtudiant);

                $entityManager->persist($etudiant);

              }
            }

            $entityManager->flush();

            return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/new.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/show", name="groupe_etudiant_show", methods={"GET"})
     */
    public function show(GroupeEtudiant $groupeEtudiant): Response
    {
        return $this->render('groupe_etudiant/show.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
            'etudiants' => $groupeEtudiant->getEtudiants()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="groupe_etudiant_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {
      $this->getUser()->checkAdmin();

      //Utilisé pour pouvoir supprimer un étudiant dans les sous groupe du groupe selectionné
      $enfants = $this->getDoctrine()->getRepository(GroupeEtudiant::class)->children($groupeEtudiant);

      //Récupération du groupe des étudiants non affecté"s pour y ajouter les étudiants supprimés si besoin
      $GroupeDesNonAffectés = $this->getDoctrine()->getRepository(GroupeEtudiant::class)->findOneByNom("Etudiants non affectés");

      /* On prépare une variable qui contiendra le groupe à partir duquel ajouter les étudiants. En effet, si le groupe
      est de haut niveau, on ajoute des étudiants depuis le groupe des étudiants non affectés, sinon on ajout des étudiants
      depuis son parent (car dans ce cas, le groupe est un sous groupe) */
      if ($groupeEtudiant->getParent() == null) {
        $groupeAPartirDuquelAjouterEtudiants = $GroupeDesNonAffectés;
      }
      else {
        $groupeAPartirDuquelAjouterEtudiants = $groupeEtudiant->getParent();
      }

      $form = $this->createForm(GroupeEtudiantEditType::class, $groupeEtudiant, ['GroupeAjout' => $groupeAPartirDuquelAjouterEtudiants]);
      $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

          if ($groupeEtudiant->getParent() == null) {

            //Le groupe est de haut niveau alors on ajoute dans le groupe et on supprime du groupe des non affectés
            foreach ($form->get('etudiantsAAjouter')->getData() as $key => $etudiant) {
             $groupeEtudiant->addEtudiant($etudiant);
             $GroupeDesNonAffectés->removeEtudiant($etudiant);
            }

            //Le groupe est de haut niveau alors on supprime l'étudiant dans les sous-groupes et dans le groupe puis on l'ajoute dans le groupe des non affectés
            foreach ($form->get('etudiantsASupprimer')->getData() as $key => $etudiant) {
              foreach ($enfants as $enfant) {
                $enfant->removeEtudiant($etudiant);
              }
              $groupeEtudiant->removeEtudiant($etudiant);
              $GroupeDesNonAffectés->addEtudiant($etudiant);
            }

          }
          else {

            //Le groupe n'est pas de haut niveau alors on ajoute juste l'étudiant dans le sous-groupe
            foreach ($form->get('etudiantsAAjouter')->getData() as $key => $etudiant) {
             $groupeEtudiant->addEtudiant($etudiant);
            }

            //Le groupe n'est pas de haut niveau alors on supprime juste l'étudiant dans le sous-groupe et ses sous-groupes
            foreach ($form->get('etudiantsASupprimer')->getData() as $key => $etudiant) {
              //On supprime l'étudiant des sous groupes
              foreach ($enfants as $enfant) {
                $enfant->removeEtudiant($etudiant);
              }
              $groupeEtudiant->removeEtudiant($etudiant);
            }

          }

          $this->getDoctrine()->getManager()->persist($groupeEtudiant);

          $this->getDoctrine()->getManager()->flush();


          return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/edit.html.twig', [
            'form' => $form->createView(),
            'groupe_etudiant' => $groupeEtudiant
        ]);
    }

    /**
     * @Route("/{id}/delete", name="groupe_etudiant_delete", methods={"GET"})
     */
    public function delete(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {

      $em = $this->getDoctrine()->getManager();
      $repo = $this->getDoctrine()->getRepository(GroupeEtudiant::class);

      $groupes = $repo->children($groupeEtudiant); /* On récupère tous les enfants du groupe courant. En effet, on a besoin
                                                      de les traiter un à un pour supprimer les évaluations liées a ceux-ci */

      $groupes[] = $groupeEtudiant; // On ajoute le groupe qu'on supprime à la liste des groupes dont on veut supprimer l'évaluation

        foreach ($groupes as $groupeAModifier) { // Pour tous les enfants du groupe choisi
          foreach ($groupeAModifier->getEvaluations() as $evaluation) { //On récupère toutes les évaluations du groupe courant
            foreach ($evaluation->getParties() as $partie) { //On récupère toutes les parties de l'évaluation courante
              foreach ($partie->getNotes() as $note) { //On récupère toutes les notes associées aux parties de l'évaluation courante
                $em->remove($note);
              }
              $em->remove($partie);
            }
            $em->remove($evaluation);
          }
        }

          //Si il s'agit d'un groupe de haut niveau, on supprime également les étudiants contenus dans le groupe
          if ($groupeEtudiant->getParent() == null) {

            foreach ($groupeEtudiant->getEtudiants() as $etudiant) {
              $em->remove($etudiant);
            }

          }

        $em->remove($groupeEtudiant); // On supprime le groupeEtudiant, ce qui grâce à Tree a pour effet de supprimer les enfants en cascade
        $em->flush();

        return $this->redirectToRoute('groupe_etudiant_index');
      }

      /**
       * @Route("/{id}/new/SousGroupe", name="groupe_etudiant_new_sousGroupe", methods={"GET","POST"})
       */
      public function NewSousFroupe(GroupeEtudiant $groupeEtudiantParent, Request $request): Response
      {
        $this->getUser()->checkAdmin();

        $groupeEtudiant = new GroupeEtudiant();
        $groupeEtudiant->setParent($groupeEtudiantParent);

        $form = $this->createForm(SousGroupeEtudiantType::class, $groupeEtudiant, ['parent' => $groupeEtudiantParent]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

          foreach ($form->get('etudiants')->getData() as $key => $etudiant) {
           $groupeEtudiant->addEtudiant($etudiant);
          }

          $this->getDoctrine()->getManager()->persist($groupeEtudiant);
          $this->getDoctrine()->getManager()->flush();

          return $this->redirectToRoute('groupe_etudiant_index');
        }


        return $this->render('groupe_etudiant/newSousGroupe.html.twig', [
            'form' => $form->createView(),
            'nomParent' => $groupeEtudiantParent->getNom()
        ]);


      }

}
