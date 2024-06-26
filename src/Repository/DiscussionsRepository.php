<?php

namespace App\Repository;

use App\Entity\Discussions;
use App\Entity\User;
use App\Entity\Offers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Discussions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discussions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discussions[]    findAll()
 * @method Discussions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscussionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discussions::class);
    }

    /**
     * User discussions
     *
     * @param User $user
     * @return array
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.offer', 'o')
            ->where('d.createdBy = :user')
            ->orWhere('o.createdBy = :user')
            ->andWhere('d.workflowState = :workflow_state')
            ->setParameters([
                'user' => $user,
                'workflow_state' => 'created'
                ])
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Users discussion about offer
     *
     * @param User $user
     * @param Offers $offer
     * @return void
     */
    public function findByUserAndOffer(User $user, Offers $offer)
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.offer', 'o')
            ->where('(d.createdBy = :user AND o.createdBy = :offer_user) OR (d.createdBy = :offer_user AND o.createdBy = :user)')
            ->andWhere('o = :offer')
            ->andWhere('d.workflowState = :workflow_state')
            ->setParameters([
                'user' => $user,
                'offer' => $offer,
                'offer_user' => $offer->getCreatedBy(),
                'workflow_state' => 'created'
                ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // /**
    //  * @return Discussions[] Returns an array of Discussions objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Discussions
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
