<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/project')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository, Request $request): Response
    {
        $user = $this->getUser();
        $showArchived = $request->query->get('archived');
        
        if ($this->isGranted('ROLE_ADMIN')) {
            if ($showArchived) {
                $projects = $projectRepository->findBy(['status' => 'archived']);
            } else {
                $projects = $projectRepository->createQueryBuilder('p')
                    ->where('p.status != :archived')
                    ->setParameter('archived', 'archived')
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()->getResult();
            }
        } else {
            if ($showArchived) {
                $projects = $projectRepository->createQueryBuilder('p')
                    ->leftJoin('p.members', 'm')
                    ->andWhere('m = :user')
                    ->andWhere('p.status = :archived')
                    ->setParameter('user', $user)
                    ->setParameter('archived', 'archived')
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()->getResult();
            } else {
                $projects = $projectRepository->createQueryBuilder('p')
                    ->leftJoin('p.members', 'm')
                    ->andWhere('m = :user')
                    ->andWhere('p.status != :archived')
                    ->setParameter('user', $user)
                    ->setParameter('archived', 'archived')
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()->getResult();
            }
        }
        return $this->render('project/index.html.twig', [
            'projects' => $projects,
            'showArchived' => $showArchived,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $project->setCreatedAt(new \DateTime());
        $project->setStatus('active');
        
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet créé avec succès !');
            return $this->redirectToRoute('app_project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        // Vérifier que l'utilisateur a accès au projet
        if (!$this->isGranted('ROLE_ADMIN') && !$project->getMembers()->contains($this->getUser())) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce projet.');
        }
        
        return $this->render('project/show.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getStatus() !== 'active') {
            $this->addFlash('warning', 'Vous ne pouvez modifier qu\'un projet actif.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Projet modifié avec succès !');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getStatus() !== 'active') {
            $this->addFlash('warning', 'Vous ne pouvez supprimer qu\'un projet actif.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
            
            $this->addFlash('success', 'Projet supprimé avec succès !');
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/{id}/status', name: 'app_project_change_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStatus(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        $validStatuses = ['active', 'completed', 'archived'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->addFlash('danger', 'Statut invalide.');
            return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
        }
        $project->setStatus($newStatus);
        $entityManager->flush();
        $this->addFlash('success', 'Statut du projet mis à jour.');
        return $this->redirectToRoute('app_project_show', ['id' => $project->getId()]);
    }
}
