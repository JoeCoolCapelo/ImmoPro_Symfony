<?php

namespace App\Controller;

use App\Entity\Visite;
use App\Form\VisiteType;
use App\Repository\BienRepository;
use App\Repository\VisiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/visites')]
class VisiteController extends AbstractController
{
    #[Route('/', name: 'visites.index', methods: ['GET'])]
    public function index(VisiteRepository $visiteRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted('VISITE_VIEW_ANY');
        $user = $this->getUser();

        $qb = $visiteRepository->createQueryBuilder('v')
            ->leftJoin('v.bien', 'b')
            ->orderBy('v.createdAt', 'DESC');

        if ($this->isGranted('ROLE_ADMIN')) {
            // Un admin voit tout, même les supprimées (soft deletes -> on peut désactiver le filter)
            // L'extension gedmo softdeleteable le gère via filters, on peut le désactiver si nécessaire.
            // \Doctrine\ORM\EntityManager::getFilters()->disable('softdeleteable');
        }

        if ($this->isGranted('ROLE_CLIENT')) {
            $qb->andWhere('v.client = :user')
               ->setParameter('user', $user);
        } elseif ($this->isGranted('ROLE_PROPRIETAIRE')) {
            $qb->andWhere('b.owner = :user')
               ->setParameter('user', $user);
        }
        // Les agents et admins voient TOUTES les visites

        // Filtrage par mot-clé (titre ou ville du bien)
        if ($search = $request->query->get('search')) {
            $qb->andWhere('b.titre LIKE :search OR b.ville LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtrage par statut
        if ($statut = $request->query->get('statut')) {
            $qb->andWhere('v.statut = :statut')
               ->setParameter('statut', $statut);
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('visites/index.html.twig', [
            'visites' => $pagination,
        ]);
    }

    #[Route('/create', name: 'visites.create', methods: ['GET', 'POST'])]
    #[IsGranted('VISITE_CREATE')]
    public function create(Request $request, BienRepository $bienRepository, EntityManagerInterface $em, \App\Service\NotificationService $notificationService): Response
    {
        $bienId = $request->query->get('bien_id');
        $bien = $bienRepository->find($bienId);
        if (!$bien) {
            throw $this->createNotFoundException('Bien introuvable.');
        }

        $visite = new Visite();
        $visite->setBien($bien);
        
        $form = $this->createForm(VisiteType::class, $visite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $visite->setClient($this->getUser());
            $visite->setStatut('en_attente');
            
            $em->persist($visite);
            $em->flush();

            // Notify the agent or owner of the new visit request
            $agent = $bien->getAgent() ?? $bien->getOwner();
            if ($agent) {
                $notificationService->notify(
                    $agent,
                    'Nouvelle demande de visite',
                    sprintf(
                        'Le client %s a demandé à visiter le bien "%s" le %s.',
                        $this->getUser()->getName(),
                        $bien->getTitre(),
                        $visite->getDateVisite() ? $visite->getDateVisite()->format('d/m/Y à H:i') : 'non spécifiée'
                    ),
                    'visite',
                    $visite->getId()
                );
            }

            $this->addFlash('success', 'Votre demande de visite a été envoyée.');
            return $this->redirectToRoute('visites.index');
        }

        return $this->render('visites/create.html.twig', [
            'form' => $form->createView(),
            'bien' => $bien,
        ]);
    }

    #[Route('/{id}', name: 'visites.show', methods: ['GET'])]
    #[IsGranted('VISITE_VIEW', subject: 'visite')]
    public function show(Visite $visite): Response
    {
        return $this->render('visites/show.html.twig', [
            'visite' => $visite,
        ]);
    }

    #[Route('/{id}', name: 'visites.update', methods: ['POST'])]
    #[IsGranted('VISITE_UPDATE', subject: 'visite')]
    public function update(Request $request, Visite $visite, EntityManagerInterface $em, \App\Service\NotificationService $notificationService): Response
    {
        $oldStatut = $visite->getStatut();
        $oldInterested = $visite->isInterested();

        $interested = $request->request->get('interested');
        if ($interested !== null) {
            $visite->setInterested(filter_var($interested, FILTER_VALIDATE_BOOLEAN));
        }

        if ($this->isGranted('VISITE_VALIDATE', $visite)) {
            $statut = $request->request->get('statut');
            if (in_array($statut, ['en_attente', 'confirmée', 'effectuée', 'annulée'])) {
                $visite->setStatut($statut);
            }

            $feedback = $request->request->get('feedback_agent');
            if ($feedback !== null) {
                $visite->setFeedbackAgent($feedback);
            }

            $commentaire = $request->request->get('commentaire');
            if ($commentaire !== null) {
                $visite->setCommentaire($commentaire);
            }
        }

        $em->flush();

        // 1. Notify client if status was updated by agent
        if ($visite->getStatut() !== $oldStatut && $visite->getClient()) {
            $notificationService->notify(
                $visite->getClient(),
                'Statut de votre visite mis à jour',
                sprintf(
                    'Votre demande de visite pour le bien "%s" a été %s par l\'agent.',
                    $visite->getBien() ? $visite->getBien()->getTitre() : 'Bien',
                    $visite->getStatut()
                ),
                'visite',
                $visite->getId()
            );
        }

        // 2. Notify agent/owner if client interest has changed
        if ($visite->isInterested() !== $oldInterested) {
            $agent = $visite->getBien() ? ($visite->getBien()->getAgent() ?? $visite->getBien()->getOwner()) : null;
            if ($agent) {
                $interestText = $visite->isInterested() ? 'très intéressé' : 'peu intéressé';
                $notificationService->notify(
                    $agent,
                    'Intérêt client mis à jour',
                    sprintf(
                        'Le client %s s\'est déclaré %s par le bien "%s" suite à sa visite.',
                        $visite->getClient() ? $visite->getClient()->getName() : 'Client',
                        $interestText,
                        $visite->getBien() ? $visite->getBien()->getTitre() : 'Bien'
                    ),
                    'visite',
                    $visite->getId()
                );
            }
        }

        if ($request->isXmlHttpRequest() || $request->getRequestFormat() === 'json') {
            return $this->json([
                'success' => true,
                'message' => 'Votre réponse a été enregistrée.',
                'interested' => $visite->isInterested()
            ]);
        }

        $this->addFlash('success', 'Votre réponse a été enregistrée. L\'agent et le propriétaire ont été notifiés.');
        return $this->redirect($request->headers->get('referer', $this->generateUrl('visites.show', ['id' => $visite->getId()])));
    }

    #[Route('/{id}/delete', name: 'visites.destroy', methods: ['POST'])]
    #[IsGranted('VISITE_DELETE', subject: 'visite')]
    public function destroy(Request $request, Visite $visite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$visite->getId(), $request->request->get('_token'))) {
            // StofDoctrineExtensionsBundle should automatically handle soft delete
            $em->remove($visite);
            $em->flush();
            $this->addFlash('success', 'Demande de visite annulée.');
        }

        return $this->redirectToRoute('visites.index');
    }
}
