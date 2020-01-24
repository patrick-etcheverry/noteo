<?php

namespace App\Controller;

use App\Entity\GroupeEtudiant;
use App\Entity\Etudiant;
use App\Form\GroupeEtudiantType;
use App\Form\GroupeEtudiantEditType;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

                  $GroupeDesNonAffectés = "Etudiants non affectés";

                  /////NOM/////
                  $indentation = "";

                  //Cette boucle détermine l'indentation du groupe en fonction de son niveau de profondeur
                  for ($i=0; $i < $node['lvl'] ; $i++) {
                    $indentation .= "&emsp;"; // $emsp désigne un ajout de 4 caractères espace
                  }

                  $nom = "<td>" . $indentation . $node['nom'] . "</td>";

                  /////EFFECTIF/////
                  $effectif = "<td>" . count($repo->find($node['id'])->getEtudiants()) . "</td>";

                  /////DESCRIPTION/////
                  $description =  "<td>" . $node['description'] . "</td>";

                  /////ENSEIGNANT/////
                  $enseignant = "<td>" . $repo->find($node['id'])->getEnseignant()->getPrenom() . " " . $repo->find($node['id'])->getEnseignant()->getNom() . "</td>";

                  /////ACTIONS/////
                  /////Cette section affiche les boutons d'actions liés à chaque groupes

                    //Consulter
                    $url = $this->generateUrl('groupe_etudiant_show', [ 'id' => $node['id'] ]);
                    $show = " <a href='$url'><i class='icon-eye'></i></a> ";

                    //Créer un sous-groupe (pas disponible pour groupe des étudiants non affectés)
                    if ($node['nom'] != $GroupeDesNonAffectés) {
                      $sousGroupe = "<a href='#'><i class='icon-plus'></i></a>";
                    }
                    else {
                      $sousGroupe = "";
                    }

                    //Créer une évaluation (seulement disponible si le groupe est évaluable)
                    if ($node['estEvaluable']) {
                      $evalSimple = "<a href='#'><i class='icon-eval-simple'></i></a>";
                      $evalParParties = "<a href='#'><i class='icon-eval-composee'></i></a>";
                    }
                    else {
                      $evalSimple = "";
                      $evalParParties = "";
                    }

                    //Modifier (pas disponible pour groupe des étudiants non affectés)
                    if ($node['nom'] != $GroupeDesNonAffectés) {
                      $url = $this->generateUrl('groupe_etudiant_edit', [ 'id' => $node['id'] ]);
                      $edit = "<a href=" . $url .  "><i class='icon-pencil-1'></i></a>";
                    }
                    else {
                      $edit = "";
                    }


                    //Supprimer (cette fonction est liée à une fenêtre modale d'id #delGroupe)
                    if ($node['nom'] != $GroupeDesNonAffectés) {
                      $delete = "<a href='#delGroupe' data-toggle='modal' data-target='#delGroupe'><i class='icon-trash' data-toggle='tooltip' title='Supprimer le groupe'></i></a>";
                    }
                    else {
                      $delete = "";
                    }

                    //Mise à la suite des actions en une seule chaîne
                    $actions = "<td>" . $show  . $sousGroupe . $evalSimple . $evalParParties . $edit . $delete . "</td>";

                  //Mise à la suite du contenu de toutes les colonnes du tableau en une seule chaîne
                  return $nom . $effectif . $description . $enseignant . $actions;
            }
        );

        //On utilise la fonction childrenHierarchy qui va créer l'arbre avec les options que l'on a choisies précédemment
        $htmlTree = $this->getDoctrine()->getRepository(GroupeEtudiant::class)->childrenHierarchy(
            null, /* starting from root nodes */
            false, /* true: load all children, false: only direct */
            $options
        );

        return $this->render('groupe_etudiant/index.html.twig', [
            'tree' => $htmlTree
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

            // Récupération du fichier CSV
            $fichierCSV = $form['fichier']->getData();

            // Ouverture du fichier CSV
            $fichier = fopen($fichierCSV,"r");

            // Pour éviter la dernière ligne du fichier
            $nbLignes = count(file($fichierCSV));

            // Récupération première ligne du fichier
            $ligne = chop(fgets($fichier));

            // Vérification première ligne du fichier
            if($ligne == "PRENOM;NOM;MAIL") {
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

        $form = $this->createForm(GroupeEtudiantEditType::class, $groupeEtudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

          foreach ($form->get('etudiants')->getData() as $key => $etudiant) {
           $groupeEtudiant->addEtudiant($etudiant);
          }

            $this->getDoctrine()->getManager()->persist($groupeEtudiant);

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="groupe_etudiant_delete", methods={"GET"})
     */
    public function delete(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {

      $em = $this->getDoctrine()->getManager();

      if (!$groupeEtudiant) {
          throw $this->createNotFoundException('Impossible de trouver un groupe correspondant');
      }

      //Suppresion : A MODIFIER pour inclure sous groupes
      $em->remove($groupeEtudiant);
      $em->flush();


      return $this->redirectToRoute('groupe_etudiant_index');
    }
}
