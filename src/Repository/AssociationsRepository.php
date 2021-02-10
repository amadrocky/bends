<?php

namespace App\Repository;

use App\Entity\Associations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Associations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Associations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Associations[]    findAll()
 * @method Associations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssociationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Associations::class);
    }

    /**
     * Filter by location
     *
     * @param [type] $location
     * @return Array
     */
    public function findByLocation($location): Array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.workflowState = :workflow_state')
            ->setParameter('workflow_state', 'active');

        if ($location != "allReg") {
            $qb
                ->andWhere('a.context LIKE :location')
                ->setParameter('location' , '%' .$location. '%');
        }
    
        return $qb
            ->orderBy('a.modifiedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Associations[] Returns an array of Associations objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Associations
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
