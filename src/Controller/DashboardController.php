<?php

namespace App\Controller;

use App\Entity\Bien;
use App\Entity\PaiementLoyer;
use App\Entity\Task;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\Visite;
use App\Repository\BienRepository;
use App\Repository\DocumentRepository;
use App\Repository\ExpenseRepository;
use App\Repository\PaiementLoyerRepository;
use App\Repository\PriceRequestRepository;
use App\Repository\SettingRepository;
use App\Repository\TaskRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\VisiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(
        EntityManagerInterface $em,
        BienRepository $bienRepo,
        TransactionRepository $transRepo,
        VisiteRepository $visiteRepo,
        UserRepository $userRepo,
        TaskRepository $taskRepo,
        PaiementLoyerRepository $paiementRepo,
        ExpenseRepository $expenseRepo,
        DocumentRepository $docRepo,
        PriceRequestRepository $priceRepo,
        SettingRepository $settingRepo
    ): Response {
        $user = $this->getUser();
        $stats = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $stats = [
                'biens_count' => $bienRepo->count([]),
                'biens_publies' => $bienRepo->count(['statut' => 'publié']),
                'biens_attente' => $bienRepo->count(['statut' => 'en_attente']),
                'transactions_count' => $transRepo->count([]),
                'total_ventes' => $transRepo->sumMontant(),
                'total_commissions' => $transRepo->sumCommissions() + $paiementRepo->sumCommissions(['statut' => 'payé']),
                'visites_attente' => $visiteRepo->count(['statut' => 'en_attente']),
                'agent_leaderboard' => $userRepo->getAgentLeaderboard(5),
                'total_volume' => $transRepo->sumMontant(['statut' => 'validée']),
                'total_users' => $userRepo->count([]),
                'total_visites' => $visiteRepo->count([]),
                'biens_validation' => $bienRepo->count(['statut' => 'en_attente']),
                'biens_en_attente_list' => $bienRepo->findBy(['statut' => 'en_attente'], ['createdAt' => 'DESC']),
                'agents' => $userRepo->createQueryBuilder('u')->andWhere('u.roles LIKE :role')->setParameter('role', '%ROLE_AGENT%')->getQuery()->getResult(),
            ];
        } elseif ($this->isGranted('ROLE_AGENT')) {
            $stats = [
                'biens_count' => $bienRepo->count(['agent' => $user]),
                'biens_publies' => $bienRepo->count(['agent' => $user, 'statut' => 'publié']),
                'biens_attente' => $bienRepo->count(['agent' => $user, 'statut' => 'en_attente']),
                'commissions_vente' => $transRepo->sumCommissions(['agent' => $user, 'statut' => 'validée', 'type' => 'vente']),
                'commissions_location' => $transRepo->sumCommissions(['agent' => $user, 'statut' => 'validée', 'type' => 'location']) 
                                        + $paiementRepo->sumCommissions(['agent' => $user, 'statut' => 'payé']),
                
                'visites_effectuees' => $visiteRepo->countByAgent($user, 'effectuée'),
                'visites_confirmees' => $visiteRepo->countByAgent($user, 'confirmée'),
                'visites_annulees' => $visiteRepo->countByAgent($user, 'annulée'),
                'visites_attente' => $visiteRepo->countByAgent($user, 'en_attente'),

                'mes_taches' => $taskRepo->findBy(['user' => $user, 'isCompleted' => false], ['createdAt' => 'DESC'], 5),
                'visites_sans_feedback' => $visiteRepo->findWithoutFeedback($user, 3),
            ];

            $transactionsCount = $transRepo->count(['agent' => $user, 'statut' => 'validée']);
            $stats['conversion_rate'] = $stats['visites_effectuees'] > 0 ? round(($transactionsCount / $stats['visites_effectuees']) * 100, 1) : 0;
        } elseif ($this->isGranted('ROLE_PROPRIETAIRE')) {
            $revenusBruts = $transRepo->sumMontantByOwner($user, 'validée');
            $commissionsAgence = $transRepo->sumCommissionsByOwner($user, 'validée');
            $totalDepenses = $expenseRepo->sumByOwner($user);

            $stats = [
                'biens_count' => $bienRepo->count(['owner' => $user]),
                'biens_publies' => $bienRepo->count(['owner' => $user, 'statut' => 'publié']),
                'revenus_totaux' => $revenusBruts,
                'total_depenses' => $totalDepenses,
                'revenu_net' => $revenusBruts - $commissionsAgence - $totalDepenses,
                'mon_patrimoine_estime' => $bienRepo->sumPrix(['owner' => $user, 'statut' => ['publié', 'en_attente']]),
                'marge_negociation' => $bienRepo->calculateMargeNegociation($user),
                'visites_recues' => $visiteRepo->countByOwner($user),
                'prochaines_visites' => $visiteRepo->findNextVisitesByOwner($user, 3),
                'derniers_feedbacks' => $visiteRepo->findLastFeedbacksByOwner($user, 3),
                'mes_documents' => $docRepo->findLastDocumentsByOwner($user, 5),
                'mes_demandes_prix' => $priceRepo->findByOwner($user, 3),
                'mon_agent' => $userRepo->findLastAgentForOwner($user),
            ];
        } else { // ROLE_CLIENT
            $stats = [
                'visites_demandees' => $visiteRepo->count(['client' => $user]),
                'visites_confirmees' => $visiteRepo->count(['client' => $user, 'statut' => 'confirmée']),
                'favoris_count' => count($user->getFavorites()),
                'transactions_effectuees' => $transRepo->count(['client' => $user]),
                'mes_locations' => $transRepo->findBy(['client' => $user, 'type' => 'location']),
                'mes_achats' => $transRepo->findBy(['client' => $user, 'type' => 'vente']),
                'prochaines_echeances' => $paiementRepo->findNextEcheancesByClient($user, 3),
                'mes_favoris' => $user->getFavorites(), // This might need slicing if too many
                'mon_agent' => $transRepo->findLastAgentForClient($user),
                'agency_phone' => $settingRepo->get('agency_phone', '22400000000'),
            ];
            // Slice favoris if needed
            $stats['mes_favoris'] = array_slice($stats['mes_favoris']->toArray(), 0, 3);
        }

        // Chart Data
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_AGENT') || $this->isGranted('ROLE_PROPRIETAIRE')) {
            $months = [];
            $biens_data = [];
            $transactions_data = [];

            for ($i = 5; $i >= 0; $i--) {
                $date = new \DateTime("-$i months");
                $months[] = $date->format('M');
                
                if ($this->isGranted('ROLE_PROPRIETAIRE')) {
                    $biens_data[] = $bienRepo->sumVuesByMonth($user, $date);
                    $transactions_data[] = $transRepo->sumMontantByOwnerMonth($user, $date);
                } else {
                    $agent = $this->isGranted('ROLE_AGENT') ? $user : null;
                    $biens_data[] = $bienRepo->countByMonth($date, $agent);
                    $transactions_data[] = $transRepo->sumMontantByMonth($date, $agent);
                }
            }

            $stats['chart_labels'] = $months;
            $stats['biens_chart'] = $biens_data;
            $stats['transactions_chart'] = $transactions_data;
        }

        return $this->render('dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }
}
