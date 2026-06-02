<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use App\Repository\VisiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/crm')]
#[IsGranted('ROLE_AGENT')]
class CRMController extends AbstractController
{
    #[Route('/', name: 'admin.crm.index', methods: ['GET'])]
    public function index(VisiteRepository $visiteRepository, TransactionRepository $transactionRepository): Response
    {
        $leads = $visiteRepository->findBy(['statut' => 'en_attente'], ['createdAt' => 'DESC']);
        $confirmed = $visiteRepository->findBy(['statut' => 'confirmée'], ['createdAt' => 'DESC']);
        $negotiations = $visiteRepository->findBy(['statut' => 'effectuée', 'interested' => true], ['createdAt' => 'DESC']);
        
        $won = $transactionRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/crm/index.html.twig', [
            'leads' => $leads,
            'confirmed' => $confirmed,
            'negotiations' => $negotiations,
            'won' => $won,
        ]);
    }
}
