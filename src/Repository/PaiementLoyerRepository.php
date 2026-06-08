<?php

namespace App\Repository;

use App\Entity\PaiementLoyer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaiementLoyer>
 */
class PaiementLoyerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaiementLoyer::class);
    }

    public function sumCommissions(array $criteria = []): float
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.commissionMontant)');
        
        foreach ($criteria as $key => $value) {
            $qb->andWhere("p.$key = :$key")
               ->setParameter($key, $value);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function findNextEcheancesByClient($client, int $limit = 3): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.transaction', 't')
            ->where('t.client = :client')
            ->andWhere("p.statut = 'en_attente'")
            ->andWhere('p.dateEcheance >= :now')
            ->setParameter('client', $client)
            ->setParameter('now', new \DateTime())
            ->orderBy('p.dateEcheance', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
