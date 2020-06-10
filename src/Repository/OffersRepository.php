<?php

namespace App\Repository;

use App\Entity\Offers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Offers|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offers|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offers[]    findAll()
 * @method Offers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OffersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offers::class);
    }

    /**
     * Research
     *
     * @param [type] $search
     * @param [type] $category
     * @param [type] $location
     * @return Array
     */
    public function getSearchResults($search, $category, $location) :Array
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.workflowState = :workflow_state')
            ->setParameter('workflow_state', 'created');

        if ($search != "") {
            $qb
                ->andWhere('o.title LIKE :search')
                ->setParameter('search' , '%' .$search. '%');
        }

        if ($category != "allCat") {
            $qb
                ->andWhere('o.category = :category')
                ->setParameter('category' , $category);
        }

        if ($location != "allReg") {
            $qb
                ->andWhere('o.context LIKE :location')
                ->setParameter('location' , '%' .$location. '%');
        }
    
        return $qb
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Offers[] Returns an array of Offers objects
    //  */
    /*
    public function findByExampleField($value)
    {
    return $this->createQueryBuilder('o')
    ->andWhere('o.exampleField = :val')
    ->setParameter('val', $value)
    ->orderBy('o.id', 'ASC')
    ->setMaxResults(10)
    ->getQuery()
    ->getResult()
    ;
    }
     */

    /*
public function findOneBySomeField($value): ?Offers
{
return $this->createQueryBuilder('o')
->andWhere('o.exampleField = :val')
->setParameter('val', $value)
->getQuery()
->getOneOrNullResult()
;
}
 */
}
