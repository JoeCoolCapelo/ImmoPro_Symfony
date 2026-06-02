<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function sumMontant(array $criteria = []): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)');
        
        foreach ($criteria as $key => $value) {
            $qb->andWhere("t.$key = :$key")
               ->setParameter($key, $value);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumCommissions(array $criteria = []): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.commissionMontant)');
        
        foreach ($criteria as $key => $value) {
            $qb->andWhere("t.$key = :$key")
               ->setParameter($key, $value);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumMontantByOwner($owner, string $statut = null): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->join('t.bien', 'b')
            ->where('b.owner = :owner')
            ->setParameter('owner', $owner);

        if ($statut) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumCommissionsByOwner($owner, string $statut = null): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.commissionMontant)')
            ->join('t.bien', 'b')
            ->where('b.owner = :owner')
            ->setParameter('owner', $owner);

        if ($statut) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function findLastAgentForClient($client)
    {
        $transaction = $this->createQueryBuilder('t')
            ->join('t.agent', 'a')
            ->where('t.client = :client')
            ->setParameter('client', $client)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $transaction?->getAgent();
    }

    public function sumMontantByMonth(\DateTime $date, $agent = null): float
    {
        $start = (clone $date)->modify('first day of this month')->setTime(0, 0);
        $end = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->where('t.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($agent) {
            $qb->andWhere('t.agent = :agent')
               ->setParameter('agent', $agent);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function sumMontantByOwnerMonth($owner, \DateTime $date): float
    {
        $start = (clone $date)->modify('first day of this month')->setTime(0, 0);
        $end = (clone $date)->modify('last day of this month')->setTime(23, 59, 59);

        return (float) $this->createQueryBuilder('t')
            ->select('SUM(t.montant)')
            ->join('t.bien', 'b')
            ->where('b.owner = :owner')
            ->andWhere('t.dateTransaction BETWEEN :start AND :end')
            ->setParameter('owner', $owner)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function getTotalVolume(): float
    {
        return $this->sumMontant();
    }

    public function getTotalCommissions(): float
    {
        return $this->sumCommissions();
    }
}
