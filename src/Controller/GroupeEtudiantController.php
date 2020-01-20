<?php

namespace App\Controller;

use App\Entity\GroupeEtudiant;
use App\Entity\Etudiant;
use App\Form\GroupeEtudiantType;
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




        $options = array(
            'decorate' => true,

            'childOpen' => '<tr>',
            'childClose' => '</tr>',
            'representationField' => 'nom',
            'nodeDecorator' => function($node) {

                /* Preparation d'un tableau vide qui donnera le nom et prénom de l'enseignant correspondant a l'id du groupe situé en index du tableau.
                Il donne également l'effectif de ce groupe. Ce tableau est nécessaire car la méthode childrenHierarchy utilisée plus bas ne renvoie pas
                d'objet enseignant correspondant au groupe, et car l'effectif n'est pas un attribtut, il faut donc le calculer. */
                $infos = array();

                $groupes = $this->getDoctrine()->getRepository(GroupeEtudiant::class)->findAll();

                foreach ($groupes as $key => $groupe) {
                  $infos[$groupe->getId()] = array("Nom" => $groupe->getEnseignant()->getNom(), "Prenom" => $groupe->getEnseignant()->getPrenom(), "Effectif" => count($groupe->getEtudiants()));
                }

                  /////NOM DU GROUPE/////
                  $indentation = "";

                  for ($i=0; $i < $node['lvl'] ; $i++) {
                    $indentation .= "&emsp;";
                  }

                  $nom = "<td>" . $indentation . $node['nom'] . "</td>";

                  /////EFFECTIF/////
                  $effectif = "<td>" . $infos[$node['id']]["Effectif"] . "</td>";

                  /////DESCRIPTION/////
                  $description =  "<td>" . $node['description'] . "</td>";

                  /////ENSEIGNANT/////
                  $enseignant = "<td>" . $infos[$node['id']]["Prenom"] . " " . $infos[$node['id']]["Nom"] . "</td>";

                  /////ACTIONS/////

                    //Show
                    $url = $this->generateUrl('groupe_etudiant_show', [ 'id' => $node['id'] ]);
                    $show = " <a href='$url'><i class='icon-eye'></i></a> ";

                    //Sous-groupe
                    $sousGroupe = "<a href='#'><i class='icon-plus'></i></a>";

                    //Est Evaluable
                    if ($node['estEvaluable']) {
                      $evalSimple = "<a href='#'><i class='icon-eval-simple'></i></a>";
                      $evalParParties = "<a href='#'><i class='icon-eval-composee'></i></a>";
                    }
                    else {
                      $evalSimple = "";
                      $evalParParties = "";
                    }

                    //edit
                    $url = $this->generateUrl('groupe_etudiant_edit', [ 'id' => $node['id'] ]);
                    $edit = "<a href=" . $url .  "><i class='icon-pencil-1'></i></a>";

                    //delete
                    $url = $this->generateUrl('groupe_etudiant_delete', [ 'id' => $node['id'] ]);
                    $delete = "<a href=" . $url . " data-toggle='modal' data-target='#delGroupe'> <i class='icon-trash' data-toggle='tooltip' title='Supprimer le groupe'></i></a>";

                    //Mise à la suite des actions
                    $actions = "<td>" . $show  . $sousGroupe . $evalSimple . $evalParParties . $edit . $delete . "</td>";

                  //Mise à la suite de toutes les colonnes du tableau
                  return $nom . $effectif . $description . $enseignant . $actions;
            }
        );

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
        $form = $this->createForm(GroupeEtudiantType::class, $groupeEtudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('groupe_etudiant_index');
        }

        return $this->render('groupe_etudiant/edit.html.twig', [
            'groupe_etudiant' => $groupeEtudiant,
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

      //Suppresion : A MODIFIER
      $em->remove($groupeEtudiant);
      $em->flush();


      return $this->redirectToRoute('groupe_etudiant_index');
    }
}
