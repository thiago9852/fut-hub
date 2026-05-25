<?php

namespace App\Form;

use App\Entity\Championship;
use App\Entity\Organization;
use App\Entity\Season;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SeasonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $options['organization'];

        $builder
            ->add('championship', EntityType::class, [
                'label' => 'Campeonato',
                'class' => Championship::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('c')
                    ->where('c.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('c.name', 'ASC'),
            ])
            ->add('name', TextType::class, ['label' => 'Nome'])
            ->add('year', IntegerType::class, ['label' => 'Ano'])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativa' => 'ATIVO', 'Encerrada' => 'ENCERRADA'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Season::class]);
        $resolver->setRequired('organization');
    }
}
