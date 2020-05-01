<?php

namespace App\Form;

use App\Entity\GroupeEtudiant;
use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotBlank;

class GroupeEtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', null, [
                'help' => 'Le nom du groupe doit contenir au moins un chiffre ou une lettre'
            ])
            ->add('description', TextareaType::class, [
              'attr' => [
                'rows' => 3
              ]
            ])
            ->add('estEvaluable', ChoiceType::class, [
              'choices' => ['Oui' => true, 'Non' => false],
              'data' => false,
              'expanded' => true,
              'label_attr' =>  [
              'class'=>'radio-inline'
              ]
            ])

            ->add('fichier', FileType::class, [
              'mapped' => false,
              'constraints' => [new NotBlank],
              'attr' => [
                'placeholder' => 'Aucun fichier choisi',
                'accept' => '.csv'
              ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GroupeEtudiant::class,
        ]);
    }
}
