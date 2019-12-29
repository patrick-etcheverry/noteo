<?php

namespace App\Form;

use App\Entity\GroupeEtudiant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupeEtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('estEvaluable')
            ->add('lft')
            ->add('lvl')
            ->add('rgt')
            ->add('enseignant')
            ->add('root')
            ->add('parent')
            ->add('etudiants')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GroupeEtudiant::class,
        ]);
    }
}
