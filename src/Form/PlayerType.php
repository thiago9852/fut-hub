<?php

namespace App\Form;

use App\Entity\Player;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('birthDate', DateType::class, ['label' => 'Data de nascimento', 'required' => false, 'widget' => 'single_text'])
            ->add('position', ChoiceType::class, [
                'label' => 'Posição',
                'required' => false,
                'choices' => [
                    'Goleiro' => 'Goleiro',
                    'Zagueiro' => 'Zagueiro',
                    'Lateral' => 'Lateral',
                    'Meio-campo' => 'Meio-campo',
                    'Atacante' => 'Atacante',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativo' => 'ATIVO', 'Inativo' => 'INATIVO'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Player::class]);
    }
}
