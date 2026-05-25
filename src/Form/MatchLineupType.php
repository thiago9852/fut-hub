<?php

namespace App\Form;

use App\Entity\GameMatch;
use App\Entity\MatchLineup;
use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Team;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatchLineupType extends AbstractType
{
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
            ->add('starter', CheckboxType::class, ['label' => 'Titular', 'required' => false])
            ->add('position', TextType::class, ['label' => 'Posição', 'required' => false])
            ->add('shirtNumber', IntegerType::class, ['label' => 'Número da camisa', 'required' => false])
            ->add('enteredAt', IntegerType::class, ['label' => 'Minuto de entrada', 'required' => false])
            ->add('leftAt', IntegerType::class, ['label' => 'Minuto de saída', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MatchLineup::class]);
        $resolver->setRequired('organization');
    }
}
