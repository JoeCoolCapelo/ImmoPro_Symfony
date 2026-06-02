<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'tasks.store', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function store(Request $request, EntityManagerInterface $em): Response
    {
        $title = $request->request->get('title');
        $dueDate = $request->request->get('due_date');

        if (!$title) {
            $this->addFlash('error', 'Le titre est requis.');
            return $this->redirect($request->headers->get('referer'));
        }

        $task = new Task();
        $task->setUser($this->getUser());
        $task->setTitle($title);
        
        if ($dueDate) {
            try {
                $task->setDueAt(new \DateTime($dueDate));
            } catch (\Exception $e) {
                // Ignore invalid date
            }
        }

        $em->persist($task);
        $em->flush();

        $this->addFlash('success', 'Tâche ajoutée !');
        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/{id}/toggle', name: 'tasks.toggle', methods: ['POST', 'GET'])]
    #[IsGranted('TASK_UPDATE', subject: 'task')]
    public function toggle(Task $task, EntityManagerInterface $em, Request $request): Response
    {
        $task->setIsCompleted(!$task->isCompleted());
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/{id}/delete', name: 'tasks.destroy', methods: ['POST'])]
    #[IsGranted('TASK_DELETE', subject: 'task')]
    public function destroy(Task $task, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $em->remove($task);
            $em->flush();
            $this->addFlash('success', 'Tâche supprimée.');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
