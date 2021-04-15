<?php

namespace App\Repository;

use App\Entity\Associations;
use App\Entity\Offers;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     *
     * @param User $user
     * @return Associations|null
     */
    public function findByUser(User $user): ?Associations
    {
        return $this->createQueryBuilder('a')
            ->where('a.createdBy = :created_by')
            ->andWhere('a.workflowState = :workflow_state')
            ->setParameters(['workflow_state' => 'active', 'created_by' => $user])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     *
     * @param Offers $offer
     * @return array
     */
    public function findByOffer(Offers $offer): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.createdBy = :created_by')
            ->andWhere('a.workflowState = :workflow_state')
            ->setParameters(['workflow_state' => 'active', 'created_by' => $offer->getCreatedBy()])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Filter by location
     *
     * @param string $location
     * @return array
     */
    public function findByLocation(string $location): array
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

    /**
     * Associations by period
     *
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    public function findByPeriod(\DateTime $start_date, \DateTime $end_date): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.createdAt >= :start_date AND a.createdAt <= :end_date')
            ->andWhere('a.workflowState = :workflow_state')
            ->setParameters([
                'workflow_state' => 'active',
                'start_date' => $start_date,
                'end_date' => $end_date
                ])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Associations array
     *
     * @return array
     */
    public function getAssociationsArray(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id', 'a.name', 'a.city', 'a.picture', 'a.createdAt', 'a.workflowState')
            ->orderBy('a.createdAt', 'DESC')
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
