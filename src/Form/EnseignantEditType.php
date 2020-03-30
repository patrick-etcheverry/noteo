<?php

namespace App\Form;

use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\NotNull;

class EnseignantEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('nom')
        ->add('prenom')
        ->add('email')
        ->add('estAdmin', ChoiceType::class, [
          'constraints' => [new NotNull],
          'help' => 'Si vous vous retirez vos droits d\'administrateur, assurez-vous qu\'une autre personne dispose de ces derniers.',
          'choices' => ['Oui' => true, 'Non' => false],
          'data' => $options['estAdmin'],
          'mapped' => false,
          'disabled' => $options['champDesactive'],
          'expanded' => true, // Pour avoir des boutons radio
          'label_attr' =>  [
            'class'=>'radio-inline' //Pour que les boutons radio soient alignés
          ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Enseignant::class,
            'champDesactive' => false,
            'estAdmin' => false
        ]);
    }
}
