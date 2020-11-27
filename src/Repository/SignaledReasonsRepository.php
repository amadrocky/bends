<?php

namespace App\Repository;

use App\Entity\SignaledReasons;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SignaledReasons|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignaledReasons|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignaledReasons[]    findAll()
 * @method SignaledReasons[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignaledReasonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignaledReasons::class);
    }

    // /**
    //  * @return SignaledReasons[] Returns an array of SignaledReasons objects
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
    public function findOneBySomeField($value): ?SignaledReasons
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
