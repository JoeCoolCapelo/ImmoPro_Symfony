<?php

namespace App\Repository;

use App\Entity\Bien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bien>
 */
class BienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bien::class);
    }

    public function sumPrix(array $criteria = []): float
    {
        $qb = $this->createQueryBuilder('b')
            ->select('SUM(b.prix)');
        
        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                $qb->andWhere($qb->expr()->in("b.$key", ":$key"))
                   ->setParameter($key, $value);
            } else {
                $qb->andWhere("b.$key = :$key")
                   ->setParameter($key, $value);
            }
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function calculateMargeNegociation($owner): float
    {
        // This is a complex query: SUM(bien.prix - transaction.montant) for SOLD/RENTED properties
        // Matching Laravel logic: $bien->prix - $transaction->montant
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('SUM(b.prix - t.montant)')
           ->from('App\Entity\Transaction', 't')
           ->join('t.bien', 'b')
           ->where('b.owner = :owner')
           ->andWhere("t.statut = 'validée'")
           ->setParameter('owner', $owner);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByMonth(\DateTime $date, $agent = null): int
    {
        $start = (clone $date)->modify('first day of this month')->setTime(0, 0);
        $end = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($agent) {
            $qb->andWhere('b.agent = :agent')
               ->setParameter('agent', $agent);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumVuesByMonth($owner, \DateTime $date): int
    {
        // Cumulative views up to the end of that month
        $end = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        return (int) $this->createQueryBuilder('b')
            ->select('SUM(b.vues)')
            ->where('b.owner = :owner')
            ->andWhere('b.createdAt <= :end')
            ->setParameter('owner', $owner)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
