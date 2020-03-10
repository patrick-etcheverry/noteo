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
  * @Route("/new", name="enseignant_new", methods={"GET","POST"})
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
    ]);
  }

  /**
  * @Route("/{id}", name="enseignant_show", methods={"GET"})
  */
  public function show(Enseignant $enseignant): Response
  {
    $this->getUser()->checkAdminOrAuthorized($enseignant);

    return $this->render('enseignant/show.html.twig', [
      'enseignant' => $enseignant,
    ]);
  }

  /**
  * @Route("/{id}/edit", name="enseignant_edit", methods={"GET","POST"})
  */
  public function edit(Request $request, Enseignant $enseignant): Response
  {
    $this->getUser()->checkAdminOrAuthorized($enseignant);

    $form = $this->createForm(EnseignantType::class, $enseignant);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('enseignant_index');
    }

    return $this->render('enseignant/edit.html.twig', [
      'enseignant' => $enseignant,
      'form' => $form->createView(),
    ]);
  }

  /**
  * @Route("/{id}", name="enseignant_delete", methods={"DELETE"})
  */
  public function delete(Request $request, Enseignant $enseignant): Response
  {
    if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($enseignant);
      $entityManager->flush();
    }

    return $this->redirectToRoute('enseignant_index');
  }
}
