<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('slug')
            ->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'choices' => [
                    'Physique' => 'physical',
                    'Numérique' => 'digital',
                ],
            ])
            ->add('price', \Symfony\Component\Form\Extension\Core\Type\MoneyType::class, [
                'currency' => 'EUR',
                'divisor' => 100,
            ])
            ->add('description')
            ->add('stock')
            ->add('weight', null, [
                'required' => false,
                'label' => 'Poids (si physique)',
            ])
            ->add('licenceKey', null, [
                'required' => false,
                'label' => 'Licence (si numérique)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
