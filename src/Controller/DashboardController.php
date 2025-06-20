<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin view: Project stats
            $stats = $projectRepository->getProjectStats();
            $recentProjects = $projectRepository->findProjects(false);
            
            return $this->render('dashboard/index.html.twig', [
                'user' => $user,
                'stats' => $stats,
                'recentProjects' => array_slice($recentProjects, 0, 5)
            ]);
        }

        // User view: Task stats
        $stats = $taskRepository->getTaskStatsForUser($user);
        $userTasks = $taskRepository->findByAssignedTo($user);
        
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'userTasks' => array_slice($userTasks, 0, 5),
        ]);
    }
}
