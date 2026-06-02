<?php

namespace App\Controller;

use App\Entity\PaiementLoyer;
use App\Entity\Transaction;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/paiements')]
class PaiementLoyerController extends AbstractController
{
    #[Route('/transaction/{id}', name: 'paiements.index', methods: ['GET'])]
    #[IsGranted('TRANSACTION_VIEW', subject: 'transaction')]
    public function index(Transaction $transaction): Response
    {
        $paiements = $transaction->getPaiementsLoyer()->toArray();
        usort($paiements, function($a, $b) {
            return $b->getDateEcheance() <=> $a->getDateEcheance();
        });
        
        return $this->render('paiements/index.html.twig', [
            'transaction' => $transaction,
            'paiements' => $paiements,
        ]);
    }

    #[Route('/{id}/payer', name: 'paiements.payer', methods: ['POST'])]
    public function marquerPaye(Request $request, PaiementLoyer $paiement, EntityManagerInterface $em, NotificationService $notificationService): Response
    {
        $this->denyAccessUnlessGranted('TRANSACTION_UPDATE', $paiement->getTransaction());

        if ($paiement->getStatut() === 'payé') {
            $this->addFlash('error', 'Ce loyer est déjà payé.');
            return $this->redirect($request->headers->get('referer'));
        }

        $paiement->setStatut('payé');
        $paiement->setDatePaiement(new \DateTime());
        $paiement->setCommentaire($request->request->get('commentaire'));

        $em->flush();

        $notificationService->sendPaiementLoyerRecu($paiement);
        
        $this->addFlash('success', 'Paiement enregistré avec succès.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/transaction/{id}/generer', name: 'paiements.generer', methods: ['POST'])]
    #[IsGranted('TRANSACTION_UPDATE', subject: 'transaction')]
    public function genererProchainMois(Transaction $transaction, EntityManagerInterface $em): Response
    {
        $dernierPaiement = null;
        foreach ($transaction->getPaiementsLoyer() as $p) {
            if (!$dernierPaiement || $p->getDateEcheance() > $dernierPaiement->getDateEcheance()) {
                $dernierPaiement = $p;
            }
        }

        $nouvelleDate = $dernierPaiement ? (clone $dernierPaiement->getDateEcheance())->modify('+1 month') : new \DateTime('first day of this month');

        $paiement = new PaiementLoyer();
        $paiement->setTransaction($transaction);
        $paiement->setBien($transaction->getBien());
        $paiement->setLocataire($transaction->getClient());
        $paiement->setAgent($transaction->getAgent());
        $paiement->setMontantLoyer($transaction->getMontant());
        $paiement->setCommissionPourcentage($transaction->getCommissionPourcentage() ?? 10.0);
        $paiement->setCommissionMontant(($paiement->getMontantLoyer() * $paiement->getCommissionPourcentage()) / 100);
        $paiement->setDateEcheance($nouvelleDate);
        $paiement->setStatut('en_attente');

        $em->persist($paiement);
        $em->flush();

        $this->addFlash('success', 'Nouveau mois généré.');
        return $this->redirect($this->generateUrl('paiements.index', ['id' => $transaction->getId()]));
    }

    #[Route('/transaction/{id}/generer-annee', name: 'paiements.generer-annee', methods: ['POST'])]
    #[IsGranted('TRANSACTION_UPDATE', subject: 'transaction')]
    public function genererAnnee(Transaction $transaction, EntityManagerInterface $em): Response
    {
        $dernierPaiement = null;
        foreach ($transaction->getPaiementsLoyer() as $p) {
            if (!$dernierPaiement || $p->getDateEcheance() > $dernierPaiement->getDateEcheance()) {
                $dernierPaiement = $p;
            }
        }

        $date = $dernierPaiement ? (clone $dernierPaiement->getDateEcheance())->modify('+1 month') : new \DateTime('first day of this month');

        for ($i = 0; $i < 12; $i++) {
            $pDate = (clone $date)->modify("+$i months");
            $paiement = new PaiementLoyer();
            $paiement->setTransaction($transaction);
            $paiement->setBien($transaction->getBien());
            $paiement->setLocataire($transaction->getClient());
            $paiement->setAgent($transaction->getAgent());
            $paiement->setMontantLoyer($transaction->getMontant());
            $paiement->setCommissionPourcentage($transaction->getCommissionPourcentage() ?? 10.0);
            $paiement->setCommissionMontant(($paiement->getMontantLoyer() * $paiement->getCommissionPourcentage()) / 100);
            $paiement->setDateEcheance($pDate);
            $paiement->setStatut('en_attente');

            $em->persist($paiement);
        }

        $em->flush();

        $this->addFlash('success', 'Échéancier annuel généré avec succès.');
        return $this->redirect($this->generateUrl('paiements.index', ['id' => $transaction->getId()]));
    }

    #[Route('/transaction/{id}/encaisser-tout', name: 'paiements.encaisser-tout', methods: ['POST'])]
    #[IsGranted('TRANSACTION_UPDATE', subject: 'transaction')]
    public function encaisserTout(Transaction $transaction, EntityManagerInterface $em, NotificationService $notificationService): Response
    {
        $count = 0;
        foreach ($transaction->getPaiementsLoyer() as $paiement) {
            if ($paiement->getStatut() !== 'payé') {
                $paiement->setStatut('payé');
                $paiement->setDatePaiement(new \DateTime());
                $count++;
            }
        }

        if ($count > 0) {
            $em->flush();
            $this->addFlash('success', "$count paiements ont été encaissés.");
        } else {
            $this->addFlash('info', 'Aucun paiement en attente.');
        }

        return $this->redirect($this->generateUrl('paiements.index', ['id' => $transaction->getId()]));
    }
}
