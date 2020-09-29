<?php

namespace App\Repository;

use App\Entity\Favorites;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Favorites|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favorites|null findOneBy(array $criteria, array $orderBy = null)
 * @method Favorites[]    findAll()
 * @method Favorites[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FavoritesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorites::class);
    }

    /**
     * User favorites
     *
     * @param User $user
     * @return void
     */
    public function findByUser(User $user)
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.workflowState = :workflow_state')
            ->setParameters([
                'user' => $user,
                'workflow_state' => 'created'
                ])
            ->orderBy('f.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Favorites[] Returns an array of Favorites objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Favorites
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
