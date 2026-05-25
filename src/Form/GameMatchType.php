<?php

namespace App\Form;

use App\Entity\GameMatch;
use App\Entity\Organization;
use App\Entity\Round;
use App\Entity\Season;
use App\Entity\Stadium;
use App\Entity\Team;
use App\Enum\MatchStatus;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GameMatchType extends AbstractType
{
    private const STATUS_LABELS = [
        'SCHEDULED' => 'Agendado',
        'LIVE' => 'Ao vivo',
        'FINISHED' => 'Encerrado',
        'POSTPONED' => 'Adiado',
        'CANCELLED' => 'Cancelado',
    ];

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
            ->add('round', EntityType::class, [
                'label' => 'Rodada',
                'class' => Round::class,
                'choice_label' => fn (Round $round) => $round->getName() ?? ('Rodada '.$round->getNumber()),
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('r')
                    ->join('r.season', 's')
                    ->join('s.championship', 'c')
                    ->where('c.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('r.number', 'ASC'),
            ])
            ->add('homeTeam', EntityType::class, [
                'label' => 'Mandante',
                'class' => Team::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                    ->where('t.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('t.name', 'ASC'),
            ])
            ->add('awayTeam', EntityType::class, [
                'label' => 'Visitante',
                'class' => Team::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('t')
                    ->where('t.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('t.name', 'ASC'),
            ])
            ->add('stadium', EntityType::class, [
                'label' => 'Estádio',
                'class' => Stadium::class,
                'choice_label' => 'name',
                'required' => false,
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('st')
                    ->where('st.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('st.name', 'ASC'),
            ])
            ->add('scheduledAt', DateTimeType::class, ['label' => 'Data/hora', 'required' => false, 'widget' => 'single_text'])
            ->add('homeScore', IntegerType::class, ['label' => 'Placar mandante', 'required' => false])
            ->add('awayScore', IntegerType::class, ['label' => 'Placar visitante', 'required' => false])
            ->add('status', EnumType::class, [
                'label' => 'Status',
                'class' => MatchStatus::class,
                'choice_label' => fn (MatchStatus $status) => self::STATUS_LABELS[$status->value],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GameMatch::class]);
        $resolver->setRequired('organization');
    }
}
