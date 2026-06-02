<?php

namespace App\Controller;

use App\Entity\PaiementLoyer;
use App\Entity\Transaction;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pdf')]
class PDFController extends AbstractController
{
    private PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    #[Route('/loyer/{id}', name: 'paiements.pdf', methods: ['GET'])]
    public function generateRentReceipt(PaiementLoyer $paiement): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') 
            && $paiement->getLocataire() !== $user 
            && $paiement->getAgent() !== $user 
            && ($paiement->getBien() && $paiement->getBien()->getOwner() !== $user)
        ) {
            throw $this->createAccessDeniedException('Accès refusé pour ce reçu de loyer.');
        }

        $binaryPdf = $this->pdfService->generateBinaryPdf('pdf/loyer_recu.html.twig', [
            'paiement' => $paiement,
            'settings' => [
                'agency_name' => 'ImmoPro',
                'agency_address' => 'Conakry, Guinée',
            ]
        ]);

        return new Response($binaryPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu_loyer_' . $paiement->getId() . '.pdf"',
        ]);
    }

    #[Route('/transaction/{id}', name: 'transactions.pdf', methods: ['GET'])]
    public function generateContract(Transaction $transaction): Response
    {
        $this->denyAccessUnlessGranted('TRANSACTION_VIEW', $transaction);

        // Check if all parties have signed
        if (!$transaction->isClientSigned() || !$transaction->isOwnerSigned() || !$transaction->isAgencySigned()) {
            $this->addFlash('error', 'Le contrat ne peut être téléchargé qu\'après signature de toutes les parties (Client, Propriétaire, Agence).');
            return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
        }

        $binaryPdf = $this->pdfService->generateBinaryPdf('pdf/contract.html.twig', [
            'transaction' => $transaction,
            'settings' => [
                'agency_name' => 'ImmoPro',
                'agency_address' => 'Conakry, Guinée',
                'agency_phone' => '+224 000 000 000',
                'agency_email' => 'contact@immopro.gn',
            ]
        ]);

        return new Response($binaryPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contrat_' . $transaction->getId() . '.pdf"',
        ]);
    }
}
