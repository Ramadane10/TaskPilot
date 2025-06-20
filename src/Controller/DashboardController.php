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
        
        // Récupérer les projets de l'utilisateur
        $userProjects = $projectRepository->findByUser($user);
        
        // Récupérer les tâches assignées à l'utilisateur
        $userTasks = $taskRepository->findByAssignedTo($user);
        
        // Statistiques
        $totalProjects = count($userProjects);
        $totalTasks = count($userTasks);
        $completedTasks = count(array_filter($userTasks, fn($task) => $task->getStatus() === 'done'));
        $pendingTasks = $totalTasks - $completedTasks;
        
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'projects' => $userProjects,
            'tasks' => $userTasks,
            'stats' => [
                'totalProjects' => $totalProjects,
                'totalTasks' => $totalTasks,
                'completedTasks' => $completedTasks,
                'pendingTasks' => $pendingTasks,
            ]
        ]);
    }
}
