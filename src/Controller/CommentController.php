<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Task;
use App\Form\CommentTypeForm;
use App\Repository\CommentRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
final class CommentController extends AbstractController
{
    #[Route('/new/{taskId}', name: 'app_comment_new', methods: ['POST'])]
    public function new(Request $request, int $taskId, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response
    {
        $task = $taskRepository->find($taskId);
        
        if (!$task) {
            throw $this->createNotFoundException('Tâche non trouvée');
        }

        // Vérifier que l'utilisateur a accès à cette tâche
        if (!$this->isGranted('ROLE_ADMIN') && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette tâche');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentTypeForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTask($task);
            $comment->setAuthor($this->getUser());
            $comment->setCreatedAt(new \DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire ajouté avec succès !');
        } else {
            // Si le formulaire n'est pas valide, afficher les erreurs
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }
        
        return $this->redirectToRoute('app_task_show', ['id' => $taskId]);
    }

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier ce commentaire
        if (!$this->isGranted('ROLE_ADMIN') && $comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce commentaire');
        }

        $form = $this->createForm(CommentTypeForm::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire modifié avec succès !');
            return $this->redirectToRoute('app_task_show', ['id' => $comment->getTask()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['GET'])]
    public function delete(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut supprimer ce commentaire
        if (!$this->isGranted('ROLE_ADMIN') && $comment->getAuthor() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce commentaire');
        }

        $taskId = $comment->getTask()->getId();
        
        $entityManager->remove($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès !');
        
        return $this->redirectToRoute('app_task_show', ['id' => $taskId]);
    }
}
