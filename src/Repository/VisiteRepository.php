<?php

namespace App\Repository;

use App\Entity\Visite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visite>
 */
class VisiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visite::class);
    }

    public function countByAgent($agent, string $statut): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.bien', 'b')
            ->where('b.agent = :agent')
            ->andWhere('v.statut = :statut')
            ->setParameter('agent', $agent)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findWithoutFeedback($agent, int $limit = 3): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.bien', 'b')
            ->where('b.agent = :agent')
            ->andWhere("v.statut = 'effectuée'")
            ->andWhere('v.feedbackAgent IS NULL')
            ->setParameter('agent', $agent)
            ->orderBy('v.dateVisite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByOwner($owner): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join('v.bien', 'b')
            ->where('b.owner = :owner')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findNextVisitesByOwner($owner, int $limit = 3): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.bien', 'b')
            ->where('b.owner = :owner')
            ->andWhere('v.dateVisite > :now')
            ->andWhere("v.statut != 'annulée'")
            ->setParameter('owner', $owner)
            ->setParameter('now', new \DateTime())
            ->orderBy('v.dateVisite', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLastFeedbacksByOwner($owner, int $limit = 3): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.bien', 'b')
            ->where('b.owner = :owner')
            ->andWhere('v.feedbackAgent IS NOT NULL')
            ->setParameter('owner', $owner)
            ->orderBy('v.dateVisite', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
