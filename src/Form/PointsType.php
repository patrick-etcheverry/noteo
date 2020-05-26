<?php

namespace App\Form;

use App\Entity\Points;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class PointsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Cet event listener permet de récupérer l'entité concernée par le formulaire et d'adapter la valeur maximale de la valeur du Points en fonction du bareme de sa partie
        $builder->addEventListener(FormEvents::POST_SET_DATA, function ($event) {
            $builder = $event->getForm(); // The FormBuilder
            $entity = $event->getData(); // The Form Object

            $builder
                ->add('valeur', NumberType::class, [
                    'required'  => true,
                    'constraints' => [
                        new LessThanOrEqual(["value" => $entity->getPartie()->getBareme(), "message" => "La note doit être inférieure à {{ compared_value }}"])
                    ]
                ])
            ;
            // Do whatever you want here!
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Points::class,
        ]);
    }
}
