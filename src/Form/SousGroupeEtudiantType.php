<?php

namespace App\Form;

use App\Entity\GroupeEtudiant;
use App\Entity\Etudiant;
use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SousGroupeEtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
          ->add('nom', TextType::class)

          ->add('description', TextareaType::class, [
            'attr' => [
              'rows' => 6
            ]
          ])
          ->add('estEvaluable', ChoiceType::class, [
            'choices' => ['Oui' => true, 'Non' => false],
            'data' => true,
            'expanded' => true,
            'label_attr' =>  [
            'class'=>'radio-inline'
            ]
          ])

          ->add('etudiants', EntityType::class, [
            'class' => Etudiant::Class, //On veut choisir des étudiants
            'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités nous même
            'mapped' => false, // Pour que l'attribut ne soit pas immédiatement mis en BD mais soit récupérable après validation
            'expanded' => true, // Pour avoir des cases
            'multiple' => true, // à cocher
            'choices' => $options['parent']->getEtudiants() // On restreint le choix à la liste des étudiants du groupe passé en parametre
          ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'parent' => null,
        ]);
    }
}
