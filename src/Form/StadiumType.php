<?php

namespace App\Form;

use App\Entity\Stadium;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StadiumType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('address', TextType::class, ['label' => 'Endereço', 'required' => false])
            ->add('city', TextType::class, ['label' => 'Cidade', 'required' => false])
            ->add('latitude', NumberType::class, ['label' => 'Latitude', 'required' => false, 'scale' => 7])
            ->add('longitude', NumberType::class, ['label' => 'Longitude', 'required' => false, 'scale' => 7])
            ->add('capacity', IntegerType::class, ['label' => 'Capacidade', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Stadium::class]);
    }
}
