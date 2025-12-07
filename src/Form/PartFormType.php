<?php

namespace App\Form;

use App\Entity\Bicycle;
use App\Entity\Part;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('manufacturer')
            ->add('price')
            ->add('bicycle', EntityType::class, [
                'class' => Bicycle::class,
                'choice_label' => 'model',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Part::class,
        ]);
    }
}
