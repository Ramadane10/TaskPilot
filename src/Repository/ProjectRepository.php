<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return Project[] Returns an array of Project objects for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.members', 'm')
            ->andWhere('m = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds projects with their members and tasks eagerly loaded.
     * @param bool $isArchived
     * @param User|null $user
     * @return Project[]
     */
    public function findProjects(bool $isArchived = false, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('m', 't') // Eager load members and tasks
            ->leftJoin('p.members', 'm')
            ->leftJoin('p.tasks', 't');

        if ($isArchived) {
            $qb->where('p.status = :status_archived');
            $qb->setParameter('status_archived', 'archived');
        } else {
            $qb->where('p.status != :status_archived');
            $qb->setParameter('status_archived', 'archived');
        }

        if ($user) {
            $qb->andWhere(':user MEMBER OF p.members');
            $qb->setParameter('user', $user);
        }

        $qb->orderBy('p.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get statistics for a user's projects.
     * @param User|null $user
     * @return array
     */
    public function getProjectStats(?User $user = null): array
    {
        // 1. Get Project stats (active, completed, archived)
        $qb = $this->createQueryBuilder('p')
            ->select(
                'SUM(CASE WHEN p.status = :active THEN 1 ELSE 0 END) as activeProjects',
                'SUM(CASE WHEN p.status = :completed THEN 1 ELSE 0 END) as completedProjects',
                'SUM(CASE WHEN p.status = :archived THEN 1 ELSE 0 END) as archivedProjects'
            )
            ->setParameter('active', 'active')
            ->setParameter('completed', 'completed')
            ->setParameter('archived', 'archived');
        
        if ($user) {
            $qb->leftJoin('p.members', 'm')
                ->where('m = :user')
                ->setParameter('user', $user);
        }
        
        $projectStats = $qb->getQuery()->getSingleResult();

        // 2. Get the list of project IDs for the user/admin to use in subsequent queries
        $projectIdsQb = $this->createQueryBuilder('p_ids');
        if ($user) {
            $projectIdsQb->select('p_ids.id')
                         ->leftJoin('p_ids.members', 'm_ids')
                         ->where('m_ids = :user')
                         ->setParameter('user', $user);
        } else {
            $projectIdsQb->select('p_ids.id');
        }
        $projectIds = array_column($projectIdsQb->getQuery()->getScalarResult(), 'id');

        if(empty($projectIds)) {
            $projectStats['totalMembers'] = 0;
            $projectStats['totalTasks'] = 0;
            return $projectStats;
        }

        // 3. Count total members for these projects
        $membersQb = $this->getEntityManager()->createQueryBuilder();
        $projectStats['totalMembers'] = $membersQb
            ->select('COUNT(DISTINCT m.id)')
            ->from(Project::class, 'p_m')
            ->join('p_m.members', 'm')
            ->where('p_m.id IN (:ids)')
            ->setParameter('ids', $projectIds)
            ->getQuery()
            ->getSingleScalarResult();

        // 4. Count total tasks for these projects
        $tasksQb = $this->getEntityManager()->createQueryBuilder();
        $projectStats['totalTasks'] = $tasksQb
            ->select('COUNT(t.id)')
            ->from(Task::class, 't')
            ->where('t.project IN (:ids)')
            ->setParameter('ids', $projectIds)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $projectStats;
    }

//    /**
//     * @return Project[] Returns an array of Project objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Project
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
