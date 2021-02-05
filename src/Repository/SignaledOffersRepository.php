<?php

namespace App\Repository;

use App\Entity\SignaledOffers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SignaledOffers|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignaledOffers|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignaledOffers[]    findAll()
 * @method SignaledOffers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignaledOffersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignaledOffers::class);
    }

    // /**
    //  * @return SignaledOffers[] Returns an array of SignaledOffers objects
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
    public function findOneBySomeField($value): ?SignaledOffers
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