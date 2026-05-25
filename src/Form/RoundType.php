<?php

namespace App\Form;

use App\Entity\Organization;
use App\Entity\Round;
use App\Entity\Season;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoundType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $options['organization'];

        $builder
            ->add('season', EntityType::class, [
                'label' => 'Temporada',
                'class' => Season::class,
                'choice_label' => fn (Season $season) => $season->getChampionship()->getName().' — '.$season->getName(),
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('s')
                    ->join('s.championship', 'c')
                    ->where('c.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('s.year', 'DESC'),
            ])
            ->add('number', IntegerType::class, ['label' => 'Número'])
            ->add('name', TextType::class, ['label' => 'Nome', 'required' => false])
            ->add('scheduledDate', DateType::class, ['label' => 'Data', 'required' => false, 'widget' => 'single_text'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Round::class]);
        $resolver->setRequired('organization');
    }
}
