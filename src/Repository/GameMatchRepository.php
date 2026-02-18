<?php

namespace App\Repository;

use App\Entity\GameMatch;
use App\Entity\Organization;
use App\Enum\MatchStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMatch>
 */
class GameMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMatch::class);
    }

    /** @return list<GameMatch> */
    public function findRecentResults(Organization $organization, int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->addSelect('ht')
            ->join('m.awayTeam', 'at')
            ->addSelect('at')
            ->where('ht.organization = :organization')
            ->andWhere('m.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('status', MatchStatus::FINISHED)
            ->orderBy('m.scheduledAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return list<GameMatch> */
    public function findUpcoming(Organization $organization, int $limit = 5): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->addSelect('ht')
            ->join('m.awayTeam', 'at')
            ->addSelect('at')
            ->where('ht.organization = :organization')
            ->andWhere('m.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('status', MatchStatus::SCHEDULED)
            ->orderBy('m.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.homeTeam', 'ht')
            ->where('ht.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function sumGoalsByOrganization(Organization $organization): int
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(COALESCE(m.homeScore, 0)) + SUM(COALESCE(m.awayScore, 0))')
            ->join('m.homeTeam', 'ht')
            ->where('ht.organization = :organization')
            ->andWhere('m.status = :status')
            ->setParameter('organization', $organization)
            ->setParameter('status', MatchStatus::FINISHED)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
