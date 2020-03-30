<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\GroupeEtudiant;
use App\Form\EtudiantType;
use App\Form\EtudiantEditType;
use App\Repository\EtudiantRepository;
use App\Repository\GroupeEtudiantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/etudiant")
 */
class EtudiantController extends AbstractController
{
    /**
     * @Route("/", name="etudiant_index", methods={"GET"})
     */
    public function index(EtudiantRepository $etudiantRepository): Response
    {
        return $this->render('etudiant/index.html.twig', [
            'etudiants' => $etudiantRepository->findAll(),
        ]);
    }

    /**
     * @Route("/nouveau", name="etudiant_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $groupeRepository = $this->getDoctrine()->getRepository(GroupeEtudiant::class);
        $groupeEtudiantsNonAffectes = $groupeRepository->findOneBySlug('etudiants-non-affectes');

        $etudiant = new Etudiant();
        $form = $this->createForm(EtudiantType::class, $etudiant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etudiant->addGroupe($groupeEtudiantsNonAffectes);
            $etudiant->setEstDemissionaire(false);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($etudiant);
            $entityManager->flush();

            return $this->redirectToRoute('etudiant_index');
        }

        return $this->render('etudiant/new.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
            'edit'=> false
        ]);
    }

    /**
     * @Route("/consulter/{id}", name="etudiant_show", methods={"GET"})
     */
    public function show(Etudiant $etudiant, GroupeEtudiantRepository $repo): Response
    {
        $groupesDeLetudiant = $repo->findAllOrderedByStudent($etudiant);
        return $this->render('etudiant/show.html.twig', [
            'etudiant' => $etudiant,
            'groupes' => $groupesDeLetudiant,
        ]);
    }

    /**
     * @Route("/modifier/{id}", name="etudiant_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Etudiant $etudiant): Response
    {

        $estDemissionaire = $etudiant->getEstDemissionaire();

        $form = $this->createForm(EtudiantEditType::class, $etudiant, ['estDemissionaire' => $estDemissionaire]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('etudiant_show', [
              'id' => $etudiant->getId()
            ]);
        }

        return $this->render('etudiant/edit.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
            'edit' => true
        ]);
    }

    /**
     * @Route("/supprimer/{id}", name="etudiant_delete", methods={"GET"})
     */
    public function delete(Etudiant $etudiant): Response
    {
        $manager = $this->getDoctrine()->getManager();

        //On supprime toutes les notes associées à l'étudiant
        foreach ($etudiant->getPoints() as $point) {
          $manager->remove($point);
        }

        //On retire l'étudiant des status auxquels il était associé
        foreach ($etudiant->getStatuts() as $statut) {
          $statut->removeEtudiant($etudiant);
        }

        //Puis on supprime l'étudiant
        $manager->remove($etudiant);

        $manager->flush();

        return $this->redirectToRoute('etudiant_index');
    }
}
