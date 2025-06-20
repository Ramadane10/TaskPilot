<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Form\CommentTypeForm;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/task')]
#[IsGranted('ROLE_USER')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'app_task_index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        $priority = $request->query->get('priority');
        $due = $request->query->get('due');
        
        // Si admin, voir toutes les tâches, sinon seulement celles assignées à l'utilisateur
        if ($this->isGranted('ROLE_ADMIN')) {
            if ($priority) {
                $tasks = $taskRepository->findByPriority($priority);
            } elseif ($due) {
                $tasks = $taskRepository->findByDueDate($due);
            } else {
                $tasks = $taskRepository->findAll();
            }
        } else {
            if ($priority) {
                $tasks = $taskRepository->findByUserAndPriority($user, $priority);
            } elseif ($due) {
                $tasks = $taskRepository->findByUserAndDueDate($user, $due);
            } else {
                $tasks = $taskRepository->findByAssignedTo($user);
            }
        }
        
        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProjectRepository $projectRepository, UserRepository $userRepository): Response
    {
        $task = new Task();
        $task->setCreatedAt(new \DateTime());
        $task->setStatus('todo');
        $task->setPriority('medium');
        
        // Récupérer les projets auxquels l'utilisateur a accès
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            $availableProjects = $projectRepository->findProjects(false);
        } else {
            $availableProjects = $projectRepository->findProjects(false, $user);
        }
        
        // Récupérer le projet sélectionné (si présent dans la requête)
        $selectedProjectId = $request->query->get('project');
        $selectedProject = null;
        $availableUsers = [];
        
        if ($selectedProjectId) {
            $selectedProject = $projectRepository->find($selectedProjectId);
            if ($selectedProject && $selectedProject->getMembers()) {
                $availableUsers = $selectedProject->getMembers()->toArray();
            }
        } else {
            // Si aucun projet n'est sélectionné, afficher tous les utilisateurs
            $availableUsers = $userRepository->findAll();
        }
        
        $form = $this->createForm(TaskType::class, $task, [
            'available_users' => $availableUsers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project = $task->getProject();
            $assignedUser = $task->getAssignedTo();
            
            // Vérifier que le projet est actif
            if ($project && $project->getStatus() !== 'active') {
                $this->addFlash('warning', 'Vous ne pouvez ajouter une tâche qu\'à un projet actif.');
                return $this->redirectToRoute('app_task_index');
            }
            
            // Vérifier que l'utilisateur assigné est membre du projet
            if ($assignedUser && $project && !$project->getMembers()->contains($assignedUser)) {
                $this->addFlash('danger', 'L\'utilisateur assigné doit être membre du projet sélectionné.');
                return $this->render('task/new.html.twig', [
                    'task' => $task,
                    'form' => $form,
                    'availableProjects' => $availableProjects,
                ]);
            }
            
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche créée avec succès !');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
            'availableProjects' => $availableProjects,
        ]);
    }

    #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        // Vérifier que l'utilisateur a accès à la tâche
        if (!$this->isGranted('ROLE_ADMIN') && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette tâche.');
        }
        
        // Créer le formulaire de commentaire
        $comment = new Comment();
        $commentForm = $this->createForm(CommentTypeForm::class, $comment);
        
        return $this->render('task/show.html.twig', [
            'task' => $task,
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier la tâche
        if (!$this->isGranted('ROLE_ADMIN') && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette tâche.');
        }
        
        // Récupérer les membres du projet pour l'assignation
        $project = $task->getProject();
        $availableUsers = $project ? $project->getMembers()->toArray() : [];
        
        $form = $this->createForm(TaskType::class, $task, [
            'available_users' => $availableUsers
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $assignedUser = $task->getAssignedTo();
            
            // Vérifier que l'utilisateur assigné est membre du projet
            if ($assignedUser && $project && !$project->getMembers()->contains($assignedUser)) {
                $this->addFlash('danger', 'L\'utilisateur assigné doit être membre du projet.');
                return $this->render('task/edit.html.twig', [
                    'task' => $task,
                    'form' => $form,
                ]);
            }
            
            $task->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès !');
            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_task_update_status', methods: ['POST'])]
    public function updateStatus(Task $task, string $status, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier la tâche
        if (!$this->isGranted('ROLE_ADMIN') && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette tâche.');
        }
        
        $validStatuses = ['todo', 'in_progress', 'review', 'done'];
        if (!in_array($status, $validStatuses)) {
            throw $this->createNotFoundException('Statut invalide.');
        }
        
        $task->setStatus($status);
        $task->setUpdatedAt(new \DateTime());
        $entityManager->flush();
        
        $this->addFlash('success', 'Statut de la tâche mis à jour !');
        return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
    }

    #[Route('/{id}', name: 'app_task_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
            
            $this->addFlash('success', 'Tâche supprimée avec succès !');
        }

        return $this->redirectToRoute('app_task_index');
    }
}
