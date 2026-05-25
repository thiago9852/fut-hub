<?php

namespace App\Form;

use App\Entity\Championship;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampionshipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('description', TextareaType::class, ['label' => 'Descrição', 'required' => false])
            ->add('format', TextType::class, ['label' => 'Formato', 'required' => false])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativo' => 'ATIVO', 'Inativo' => 'INATIVO'],
            ])
            ->add('startDate', DateType::class, ['label' => 'Início', 'required' => false, 'widget' => 'single_text'])
            ->add('endDate', DateType::class, ['label' => 'Fim', 'required' => false, 'widget' => 'single_text'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Championship::class]);
    }
}
