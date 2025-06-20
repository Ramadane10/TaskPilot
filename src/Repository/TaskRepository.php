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

    /**
     * @return Task[] Returns an array of Task objects filtered by due date
     */
    public function findByDueDate(string $dueFilter): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.status != :done')
            ->setParameter('done', 'done');

        switch ($dueFilter) {
            case 'overdue':
                $qb->andWhere('t.dueDate < :now')
                   ->setParameter('now', new \DateTime());
                break;
            case 'today':
                $today = new \DateTime();
                $qb->andWhere('t.dueDate >= :start')
                   ->andWhere('t.dueDate < :end')
                   ->setParameter('start', $today->format('Y-m-d 00:00:00'))
                   ->setParameter('end', $today->format('Y-m-d 23:59:59'));
                break;
            case 'week':
                $today = new \DateTime();
                $weekEnd = clone $today;
                $weekEnd->modify('+7 days');
                $qb->andWhere('t.dueDate >= :start')
                   ->andWhere('t.dueDate < :end')
                   ->setParameter('start', $today->format('Y-m-d 00:00:00'))
                   ->setParameter('end', $weekEnd->format('Y-m-d 23:59:59'));
                break;
        }

        return $qb->orderBy('t.dueDate', 'ASC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * @return Task[] Returns an array of Task objects assigned to a user and filtered by due date
     */
    public function findByUserAndDueDate(User $user, string $dueFilter): array
    {
        $qb = $this->createQueryBuilder('t')
            ->andWhere('t.assignedTo = :user')
            ->andWhere('t.dueDate IS NOT NULL')
            ->andWhere('t.status != :done')
            ->setParameter('user', $user)
            ->setParameter('done', 'done');

        switch ($dueFilter) {
            case 'overdue':
                $qb->andWhere('t.dueDate < :now')
                   ->setParameter('now', new \DateTime());
                break;
            case 'today':
                $today = new \DateTime();
                $qb->andWhere('t.dueDate >= :start')
                   ->andWhere('t.dueDate < :end')
                   ->setParameter('start', $today->format('Y-m-d 00:00:00'))
                   ->setParameter('end', $today->format('Y-m-d 23:59:59'));
                break;
            case 'week':
                $today = new \DateTime();
                $weekEnd = clone $today;
                $weekEnd->modify('+7 days');
                $qb->andWhere('t.dueDate >= :start')
                   ->andWhere('t.dueDate < :end')
                   ->setParameter('start', $today->format('Y-m-d 00:00:00'))
                   ->setParameter('end', $weekEnd->format('Y-m-d 23:59:59'));
                break;
        }

        return $qb->orderBy('t.dueDate', 'ASC')
                 ->getQuery()
                 ->getResult();
    }

    /**
     * Get statistics for a user's tasks.
     * @param User $user
     * @return array
     */
    public function getTaskStatsForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->select('
                COUNT(t.id) as total,
                SUM(CASE WHEN t.status = :todo THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN t.status = :in_progress THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN t.status = :review THEN 1 ELSE 0 END) as review,
                SUM(CASE WHEN t.status = :done THEN 1 ELSE 0 END) as done
            ')
            ->where('t.assignedTo = :user')
            ->setParameter('user', $user)
            ->setParameter('todo', 'todo')
            ->setParameter('in_progress', 'in_progress')
            ->setParameter('review', 'review')
            ->setParameter('done', 'done')
            ->getQuery()
            ->getSingleResult();
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
