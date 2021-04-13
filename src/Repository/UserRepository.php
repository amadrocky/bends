<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Users by period
     *
     * @param \DateTime $start_date
     * @param \DateTime $end_date
     * @return array
     */
    public function findByPeriod(\DateTime $start_date, \DateTime $end_date): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.createdAt >= :start_date AND u.createdAt <= :end_date')
            ->andWhere('u.workflowState IN (:workflow_state)')
            ->setParameters([
                'workflow_state' => ['created', 'active'],
                'start_date' => $start_date,
                'end_date' => $end_date
                ])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Users array
     *
     * @return array
     */
    public function getUsersArray(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id', 'u.email', 'u.firstname', 'u.lastname', 'u.createdAt', 'u.workflowState', 'u.modifiedAt', 'u.profilImage', 'u.pseudonym')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
