<?php

namespace App\Form;

use App\Repository\EtudiantRepository;
use App\Entity\Statut;
use App\Entity\Enseignant;
use App\Entity\Etudiant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class StatutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('description', TextareaType::class, [
              'attr' => [
                'rows' => 3
              ]
            ])
            ->add('lesEtudiantsAAjouter', EntityType::class, [
                'class' => Etudiant::Class, //On veut choisir des étudiants
                'choice_label' => false, // On n'affichera pas d'attribut de l'entité à côté du bouton pour aider au choix car on liste les entités nous même
                'label' => false,
                'mapped' => false, // Pour que l'attribut ne soit pas immédiatement mis en BD mais soit récupérable après validation
                'expanded' => true, // Pour avoir des cases
                'multiple' => true, // à cocher
                'choices' => $options['etudiants'] // On restreint le choix à la liste des étudiants du groupe passé en parametre
              ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Statut::class,
            'etudiants' => null
        ]);
    }
}
