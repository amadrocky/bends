<?php

namespace App\Repository;

use App\Entity\Offers;
use App\Entity\Associations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

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
     * Undocumented function
     *
     * @param string $search
     * @param [type] $category
     * @param string $location
     * @param boolean $isAsso
     * @return array
     */
    public function getSearchResults(string $search, $category, string $location, bool $isAsso = false): array
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

        if ($isAsso) {
            $qb
                ->innerJoin(Associations::class, 'a')
                ->andWhere('o.createdBy = a.createdBy');
        }
    
        return $qb
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Associations offers
     *
     * @return array
     */
    public function findByAssociations(): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin(Associations::class, 'a')
            ->where('o.createdBy = a.createdBy')
            ->andWhere('o.workflowState = :workflow_state')
            ->setParameter('workflow_state', 'created')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Three last offers by association
     *
     * @param Associations $association
     * @return array
     */
    public function findByAssociation(Associations $association): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.createdBy', 'user')
            ->where('user = :association')
            ->andWhere('o.workflowState = :workflow_state')
            ->setParameters(['workflow_state' => 'created', 'association' => $association->getCreatedBy()])
            ->distinct()
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Offers by period
     *
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    public function findByPeriod(\DateTime $start_date, \DateTime $end_date): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.createdAt >= :start_date AND o.createdAt <= :end_date')
            ->andWhere('o.workflowState = :workflow_state')
            ->setParameters([
                'workflow_state' => 'created',
                'start_date' => $start_date,
                'end_date' => $end_date
                ])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Get the top five of offers by categories
     *
     * @return array
     */
    public function getByCategories(): array
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id) as nbOffers', 'IDENTITY(o.category) as category_id', 'c.name as category_name')
            ->innerJoin('o.category', 'c')
            ->where('o.workflowState = :workflow_state')
            ->setParameter('workflow_state', 'created')
            ->groupBy('category_id')
            ->orderBy('nbOffers', 'DESC')
            ->setMaxResults(5)
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
