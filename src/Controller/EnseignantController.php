<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Form\EnseignantType;
use App\Repository\EnseignantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
* @Route("/enseignant")
*/
class EnseignantController extends AbstractController
{
  /**
  * @Route("/", name="enseignant_index", methods={"GET"})
  */
  public function index(EnseignantRepository $enseignantRepository): Response
  {
    $this->getUser()->checkAdmin();

    return $this->render('enseignant/index.html.twig', [
      'enseignants' => $enseignantRepository->findAll(),
    ]);
  }

  /**
  * @Route("/nouveau", name="enseignant_new", methods={"GET","POST"})
  */
  public function new(Request $request, ObjectManager $manager, UserPasswordEncoderInterface $encoder): Response
  {
    $this->getUser()->checkAdmin();

    $enseignant = new Enseignant();
    $form = $this->createForm(EnseignantType::class, $enseignant);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      // Si Oui au bouton radio
      if ($form['estAdmin']->getData()) {
        $enseignant->setRoles(['ROLE_USER','ROLE_ADMIN']);
      }
      else {
        $enseignant->setRoles(['ROLE_USER']);
      }

      $mdpEncode = $encoder->encodePassword($enseignant, $enseignant->getPassword());
      $enseignant->setPassword($mdpEncode);

      $manager->persist($enseignant);
      $manager->flush();

      return $this->redirectToRoute('enseignant_index');
    }

    return $this->render('enseignant/new.html.twig', [
      'enseignant' => $enseignant,
      'form' => $form->createView(),
      'label' => 'Créer l\'enseignant'
    ]);
  }

  /**
  * @Route("/consulter/{id}", name="enseignant_show", methods={"GET"})
  */
  public function show(Enseignant $enseignant): Response
  {
    $this->getUser()->checkAdminOrAuthorized($enseignant);

    return $this->render('enseignant/show.html.twig', [
      'enseignant' => $enseignant,
    ]);
  }

  /**
  * @Route("/modifier/{id}", name="enseignant_edit", methods={"GET","POST"})
  */
  public function edit(Request $request, Enseignant $enseignant, UserPasswordEncoderInterface $encoder): Response
  {
    $this->getUser()->checkAdminOrAuthorized($enseignant);

    // On verifie le rôle de l'utilisateur pour désactiver ou non les boutons radios permettant de définir le rôle
    $champDesactive = false;
    if (!$this->getUser()->isAdmin()) {
      $champDesactive = true;
    }

    $form = $this->createForm(EnseignantType::class, $enseignant, ['champDesactive' => $champDesactive]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      // Si Oui au bouton radio
      if ($form['estAdmin']->getData()) {
        $enseignant->setRoles(['ROLE_USER','ROLE_ADMIN']);
      }
      else {
        $enseignant->setRoles(['ROLE_USER']);
      }

      $mdpEncode = $encoder->encodePassword($enseignant, $enseignant->getPassword());
      $enseignant->setPassword($mdpEncode);
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('enseignant_index');
    }

    return $this->render('enseignant/edit.html.twig', [
      'enseignant' => $enseignant,
      'form' => $form->createView(),
      'label' => 'Enregistrer'
    ]);
  }

  /**
  * @Route("/supprimer/{id}", name="enseignant_delete", methods={"GET","POST"})
  */
  public function delete(Request $request, Enseignant $enseignant): Response
  {
      $this->getUser()->checkAdmin();

      //Pour qu'un administrateur ne supprime pas son propre profil
      if($this->getUser()->getId() != $enseignant->getId()) {
          foreach ($enseignant->getStatuts() as $statut) {
              //La méthode forward permet d'éxécuter l'action métier d'un controlleur donné mais ne redirige pas l'utilisateur,
              // ce qui permet de ne pas dupliquer le code de la suppression dans ce cas
              $this->forward('App\Controller\StatutController::delete', [
                  'id'  => $statut->getId(),
              ]);
          }

          foreach ($enseignant->getGroupes() as $groupe) {
              $this->forward('App\Controller\GroupeEtudiantController::delete', [
                  'id'  => $groupe->getId(),
              ]);
          }

          foreach ($enseignant->getEvaluations() as $evaluation) {
              $this->forward('App\Controller\EvaluationController::delete', [
                  'id'  => $evaluation->getId(),
              ]);
          }
      }

      $entityManager = $this->getDoctrine()->getManager();

      $entityManager->remove($enseignant);
      $entityManager->flush();


    return $this->redirectToRoute('enseignant_index');
  }
}
