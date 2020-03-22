<?php

namespace App\Form;

use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EnseignantType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
    ->add('nom')
    ->add('prenom')
    ->add('email')
    ->add('estAdmin', ChoiceType::class, [
      'choices' => ['Oui' => true, 'Non' => false],
      'data' => $options['estAdmin'],
      'mapped' => false,
      'disabled' => $options['champDesactive'],
      'expanded' => true, // Pour avoir des boutons radio
      'label_attr' =>  [
        'class'=>'radio-inline' //Pour que les boutons radio soient alignés
      ]
    ])
    ->add('password',RepeatedType::class, [
      'type' => PasswordType::class,
      'invalid_message' => 'Les mots de passe saisis ne correspondent pas.',
      'options' => ['attr' => ['class' => 'password-field']],
      'required' => true,
      'first_options'  => ['label' => 'Saisissez le une première fois...'],
      'second_options' => ['label' => 'Puis une deuxième fois...'],
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
