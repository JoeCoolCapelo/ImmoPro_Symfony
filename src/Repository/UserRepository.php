<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    public function getAgentLeaderboard(int $limit = 5): array
    {
        // SELECT u.*, COUNT(t.id) as trans_count, SUM(t.montant) as total_montant 
        // FROM user u JOIN transaction t ON t.agent_id = u.id ...
        return $this->createQueryBuilder('u')
            ->select('u, COUNT(t.id) AS HIDDEN trans_count, SUM(t.montant) AS HIDDEN total_montant')
            ->leftJoin('u.transactionsAsAgent', 't')
            ->where("u.roles LIKE :role")
            ->setParameter('role', '%ROLE_AGENT%')
            ->andWhere("t.statut = 'validée' OR t.id IS NULL")
            ->groupBy('u.id')
            ->orderBy('trans_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findLastAgentForOwner($owner)
    {
        return $this->createQueryBuilder('u')
            ->select('u')
            ->join('u.biensGeres', 'b')
            ->where('b.owner = :owner')
            ->setParameter('owner', $owner)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
