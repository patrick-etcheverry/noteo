<?php

namespace App\Controller;

use App\Repository\EtudiantRepository;
use App\Repository\GroupeEtudiantRepository;
use App\Repository\StatutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class NoteoController extends AbstractController
{
  /**
  * @Route("/", name="noteo_login")
  */
  public function index()
  {
    return $this->redirectToRoute('app_login');
  }

    /**
     * @Route("/options", name="noteo_options")
     */
    public function optionsApplication()
    {
        $this->denyAccessUnlessGranted('OPTIONS_APPLICATION', $this->getUser());
        return $this->render("app/optionsApplication.html.twig");

    }

    /**
     * @Route("/preparation-annee-suivante", name="preparer_annee_suivante")
     */
    public function preparerAnneeSuivante(GroupeEtudiantRepository $repoGroupe, StatutRepository $repoStatut, EtudiantRepository $repoEtudiant)
    {
        $entityManager = $this->getDoctrine()->getManager();
        foreach ($repoGroupe->findAll() as $groupe) {
            foreach ($groupe->getEvaluations() as $evaluation) {
                foreach ($evaluation->getParties() as $partie) {
                    foreach ($partie->getNotes() as $note) {
                        $entityManager->remove($note);
                    }
                    $entityManager->remove($partie);
                }
                $entityManager->remove($evaluation);
            }
            $entityManager->remove($groupe);
        }
        foreach ($repoStatut->findAll() as $statut) {
            $entityManager->remove($statut);
        }
        foreach ($repoEtudiant->findAll() as $etudiant) {
            $entityManager->remove($etudiant);
        }
        $entityManager->flush();
        return $this->redirectToRoute("noteo_options");
    }
}
