<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'notifications.index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $notifications = $notificationRepository->findAllForUser($this->getUser());

        return $this->render('notifications/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/{id}/read', name: 'notifications.read', methods: ['POST'])]
    public function read(Notification $notification, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$notification->isRead()) {
            $notification->setReadAt(new \DateTimeImmutable());
            $em->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->addFlash('success', 'Notification marquée comme lue.');
        return $this->redirect($request->headers->get('referer', $this->generateUrl('notifications.index')));
    }

    #[Route('/read-all', name: 'notifications.read_all', methods: ['POST'])]
    public function readAll(NotificationRepository $notificationRepository, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $unread = $notificationRepository->findBy([
            'user' => $this->getUser(),
            'readAt' => null
        ]);

        foreach ($unread as $notification) {
            $notification->setReadAt(new \DateTimeImmutable());
        }

        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');
        return $this->redirect($request->headers->get('referer', $this->generateUrl('notifications.index')));
    }
}
