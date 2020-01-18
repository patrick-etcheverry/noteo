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
        $repo = $this->getDoctrine()->getRepository(GroupeEtudiant::class);

        /* Preparation d'un tableau vide qui donnera le nom et prénom de l'enseignant correspondant a l'id du groupe situé en index du tableau.
        Il donne également l'effectif de ce groupe. Ce tableau est nécessaire car la méthode childrenHierarchy utilisée plus bas ne renvoie pas
        d'objet enseignant correspondant au groupe, et car l'effectif n'est pas un attribtut, il faut donc le calculer. */
        $infos = array();

        //Remplissage du tableau
        $groupes = $repo->findAll();
        foreach ($groupes as $key => $groupe) {
          $infos[$groupe->getId()] = array("Nom" => $groupe->getEnseignant()->getNom(), "Prenom" => $groupe->getEnseignant()->getPrenom(), "Effectif" => count($groupe->getEtudiants()));
        }


        return $this->render('groupe_etudiant/index.html.twig', [
            'infos' => $infos,
            'arbre' => $repo->childrenHierarchy(),
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
            if($ligne == "NOM;PRENOM;MAIL") {
              for ($i=0; $i < $nbLignes - 1; $i++) {
                $ligne = fgets($fichier);
                $ligneDecoupee = explode(";",$ligne);

                // Création de l'étudiant
                $etudiant = new Etudiant();
                $etudiant->setNom($ligneDecoupee[0]);
                $etudiant->setPrenom($ligneDecoupee[1]);
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
     * @Route("/{id}", name="groupe_etudiant_show", methods={"GET"})
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
     * @Route("/{id}", name="groupe_etudiant_delete", methods={"DELETE"})
     */
    public function delete(Request $request, GroupeEtudiant $groupeEtudiant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$groupeEtudiant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($groupeEtudiant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('groupe_etudiant_index');
    }
}
