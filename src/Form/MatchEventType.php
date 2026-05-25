<?php

namespace App\Form;

use App\Entity\GameMatch;
use App\Entity\MatchEvent;
use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Team;
use App\Enum\MatchEventType as MatchEventTypeEnum;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType as FormEnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchEventType extends AbstractType
{
    private const TYPE_LABELS = [
        'GOAL' => 'Gol',
        'ASSIST' => 'Assistência',
        'YELLOW_CARD' => 'Cartão amarelo',
        'RED_CARD' => 'Cartão vermelho',
        'SUBSTITUTION_IN' => 'Entrada (substituição)',
        'SUBSTITUTION_OUT' => 'Saída (substituição)',
        'OWN_GOAL' => 'Gol contra',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organization $organization */
        $organization = $options['organization'];

        $builder
            ->add('match', EntityType::class, [
                'label' => 'Jogo',
                'class' => GameMatch::class,
                'choice_label' => fn (GameMatch $match) => $match->getHomeTeam()->getName().' × '.$match->getAwayTeam()->getName(),
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('m')
                    ->join('m.homeTeam', 'ht')
                    ->where('ht.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('m.scheduledAt', 'DESC'),
            ])
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
            ->add('type', FormEnumType::class, [
                'label' => 'Tipo',
                'class' => MatchEventTypeEnum::class,
                'choice_label' => fn (MatchEventTypeEnum $type) => self::TYPE_LABELS[$type->value],
            ])
            ->add('minute', IntegerType::class, ['label' => 'Minuto', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchEvent::class]);
        $resolver->setRequired('organization');
    }
}
