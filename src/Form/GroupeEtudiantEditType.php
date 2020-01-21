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

class GroupeEtudiantEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
          ->add('nom', TextType::class, [
            'attr' => [
              'placeholder' => 'DUT Info'
            ]
          ])

          ->add('description', TextareaType::class, [
            'attr' => [
              'placeholder' => 'Une belle description',
              'rows' => 6
            ]
          ])

          ->add('enseignant', EntityType::class, [
            'class' => Enseignant::class, 'choice_label' => 'nom'
          ])

          ->add('estEvaluable', ChoiceType::class, [
            'choices' => ['Oui' => true, 'Non' => false],
            'expanded' => true,
            'label_attr' =>  [
            'class'=>'radio-inline'
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
