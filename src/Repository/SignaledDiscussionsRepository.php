<?php

namespace App\Repository;

use App\Entity\SignaledDiscussions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SignaledDiscussions|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignaledDiscussions|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignaledDiscussions[]    findAll()
 * @method SignaledDiscussions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignaledDiscussionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignaledDiscussions::class);
    }

    // /**
    //  * @return SignaledDiscussions[] Returns an array of SignaledDiscussions objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SignaledDiscussions
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
