<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Get unreads messages
     *
     * @param User $user
     * @return array
     */
    public function findUnreads(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.discussion', 'd')
            ->innerJoin('d.offer', 'o')
            ->where('d.createdBy = :user OR o.createdBy = :user')
            ->andWhere('m.workflowState = :workflowState')
            ->andWhere('m.createdBy != :user')
            ->setParameters([
                'user' => $user,
                'workflowState' => 'created',
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Message[] Returns an array of Message objects
    //  */
    /*
    public function findByExampleField($value)
    {
    return $this->createQueryBuilder('m')
    ->andWhere('m.exampleField = :val')
    ->setParameter('val', $value)
    ->orderBy('m.id', 'ASC')
    ->setMaxResults(10)
    ->getQuery()
    ->getResult()
    ;
    }
     */

    /*
public function findOneBySomeField($value): ?Message
{
return $this->createQueryBuilder('m')
->andWhere('m.exampleField = :val')
->setParameter('val', $value)
->getQuery()
->getOneOrNullResult()
;
}
 */
}
