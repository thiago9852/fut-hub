<?php

namespace App\Form;

use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Season;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeamPlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $options['organization'];

        $builder
            ->add('team', EntityType::class, [
                'label' => 'Time',
                'class' => Team::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                    ->where('t.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('t.name', 'ASC'),
            ])
            ->add('player', EntityType::class, [
                'label' => 'Jogador',
                'class' => Player::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where('p.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('p.name', 'ASC'),
            ])
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
            ->add('shirtNumber', IntegerType::class, ['label' => 'Número da camisa', 'required' => false])
            ->add('startedAt', DateType::class, ['label' => 'Início', 'required' => false, 'widget' => 'single_text'])
            ->add('endedAt', DateType::class, ['label' => 'Fim', 'required' => false, 'widget' => 'single_text'])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => ['Ativo' => 'ATIVO', 'Inativo' => 'INATIVO'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TeamPlayer::class]);
        $resolver->setRequired('organization');
    }
}
