<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * @return Task[] Returns an array of Task objects assigned to a specific user
     */
    public function findByAssignedTo(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects filtered by priority
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.priority = :priority')
            ->setParameter('priority', $priority)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects assigned to a user and filtered by priority
     */
    public function findByUserAndPriority(User $user, string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.priority = :priority')
            ->setParameter('user', $user)
            ->setParameter('priority', $priority)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects ordered by priority (urgent first)
     */
    public function findByAssignedToOrderedByPriority(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('CASE t.priority 
                WHEN \'urgent\' THEN 1 
                WHEN \'high\' THEN 2 
                WHEN \'medium\' THEN 3 
                WHEN \'low\' THEN 4 
                ELSE 5 END', 'ASC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Task[] Returns an array of Task objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Task
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
