<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Repository\BienRepository;
use App\Repository\ExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/expenses')]
#[IsGranted('ROLE_AGENT')]
class ExpenseController extends AbstractController
{
    #[Route('/', name: 'expenses.index', methods: ['GET'])]
    public function index(ExpenseRepository $expenseRepository, BienRepository $bienRepository): Response
    {
        $expenses = $expenseRepository->findBy([], ['date' => 'DESC']);
        $biens = $bienRepository->findAll();

        return $this->render('admin/expenses/index.html.twig', [
            'expenses' => $expenses,
            'biens' => $biens,
        ]);
    }

    #[Route('/store', name: 'expenses.store', methods: ['POST'])]
    public function store(Request $request, EntityManagerInterface $em, BienRepository $bienRepository): Response
    {
        $bienId = $request->request->get('bien_id');
        $amount = $request->request->get('amount');
        $date = $request->request->get('date');
        $description = $request->request->get('description');

        $bien = $bienRepository->find($bienId);

        if (!$bien || !$amount || !$date) {
            $this->addFlash('error', 'Veuillez remplir tous les champs obligatoires.');
            return $this->redirectToRoute('expenses.index');
        }

        $expense = new Expense();
        $expense->setBien($bien);
        $expense->setAmount($amount);
        $expense->setDate(new \DateTime($date));
        $expense->setDescription($description);

        $em->persist($expense);
        $em->flush();

        $this->addFlash('success', 'Dépense enregistrée avec succès.');

        return $this->redirectToRoute('expenses.index');
    }

    #[Route('/{id}/delete', name: 'expenses.delete', methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->request->get('_token'))) {
            $em->remove($expense);
            $em->flush();
            $this->addFlash('success', 'Dépense supprimée.');
        }

        return $this->redirectToRoute('expenses.index');
    }
}
