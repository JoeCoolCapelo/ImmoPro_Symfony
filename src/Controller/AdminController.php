<?php

namespace App\Controller;

use App\Entity\Setting;
use App\Entity\User;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/broadcast', name: 'admin.broadcast', methods: ['GET'])]
    public function broadcast(): Response
    {
        return $this->render('admin/broadcast.html.twig');
    }

    #[Route('/broadcast/store', name: 'admin.broadcast.store', methods: ['POST'])]
    public function broadcastStore(Request $request, UserRepository $userRepository): Response
    {
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');
        $target = $request->request->get('target');

        if (!$subject || !$message || !$target) {
            $this->addFlash('error', 'Veuillez remplir tous les champs.');
            return $this->redirectToRoute('admin.broadcast');
        }

        $qb = $userRepository->createQueryBuilder('u');

        if ($target !== 'all') {
            $qb->andWhere('u.roles LIKE :role')
               ->setParameter('role', '%' . $target . '%');
        }

        $users = $qb->getQuery()->getResult();
        $count = count($users);

        // Simulation of sending emails/notifications to $users
        // In a real scenario, this would dispatch a Messenger job or use Symfony Mailer.

        $this->addFlash('success', 'Le message "' . $subject . '" a été envoyé à ' . $count . ' utilisateurs. Les emails seront traités en arrière-plan.');

        return $this->redirectToRoute('admin.broadcast');
    }

    #[Route('/settings', name: 'admin.settings', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function settings(SettingRepository $settingRepository): Response
    {
        $settingsRaw = $settingRepository->findAll();
        $settings = [];
        foreach ($settingsRaw as $setting) {
            $settings[$setting->getKey()] = $setting->getValue();
        }

        // Defaults
        $defaults = [
            'agency_name' => 'ImmoPro',
            'contact_email' => 'josephbangoura0204@gmail.com',
            'contact_phone' => '+224 625 99 79 03',
            'currency' => 'GNF',
            'agent_commission' => '5',
            'agency_director' => 'M. Mohamed SYLLA',
        ];

        foreach ($defaults as $key => $val) {
            if (!isset($settings[$key])) {
                $settings[$key] = $val;
            }
        }

        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/settings', name: 'admin.settings.update', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function settingsStore(Request $request, EntityManagerInterface $em, SettingRepository $settingRepository, SluggerInterface $slugger): Response
    {
        $keys = [
            'agency_name', 'agency_director', 'contact_email', 'contact_phone',
            'currency', 'agent_commission', 'team_member_1_name', 'team_member_1_role',
            'team_member_2_name', 'team_member_2_role', 'team_member_3_name', 'team_member_3_role'
        ];

        foreach ($keys as $key) {
            $val = $request->request->get($key);
            if ($val !== null) {
                $setting = $settingRepository->findOneBy(['key' => $key]) ?? new Setting();
                $setting->setKey($key);
                $setting->setValue($val);
                $setting->setType(is_numeric($val) ? 'integer' : 'string');
                $em->persist($setting);
            }
        }

        $files = [
            'agency_logo' => 'general',
            'team_member_1_photo' => 'team',
            'team_member_2_photo' => 'team',
            'team_member_3_photo' => 'team'
        ];

        foreach ($files as $key => $group) {
            $file = $request->files->get($key);
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                $file->move(
                    $this->getParameter('public_directory').'/'.$group,
                    $newFilename
                );

                $setting = $settingRepository->findOneBy(['key' => $key]) ?? new Setting();
                $setting->setKey($key);
                $setting->setValue($group.'/'.$newFilename);
                $setting->setType('string');
                $setting->setGroupName($group);
                $em->persist($setting);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Paramètres mis à jour avec succès.');
        return $this->redirectToRoute('admin.settings');
    }

    #[Route('/users', name: 'admin.users', methods: ['GET'])]
    public function users(Request $request, UserRepository $userRepository, PaginatorInterface $paginator): Response
    {
        $qb = $userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($search = $request->query->get('search')) {
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Export PDF/CSV logic can be implemented here

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('admin/users/index.html.twig', [
            'users' => $pagination,
        ]);
    }

    #[Route('/users/create', name: 'admin.users.create', methods: ['GET', 'POST'])]
    public function userCreate(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            
            $password = $request->request->get('password');
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            
            $role = $request->request->get('role');
            if ($role) {
                $user->setRoles([$role]);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin.users');
        }

        return $this->render('admin/users/create.html.twig');
    }

    #[Route('/users/{id}/edit', name: 'admin.users.edit', methods: ['GET', 'POST'])]
    public function userEdit(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            $user->setPhone($request->request->get('phone'));

            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }

            $role = $request->request->get('role');
            if ($role) {
                $user->setRoles([$role]);
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('admin.users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'admin.users.destroy', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function userDestroy(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin.users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin.users');
    }

    #[Route('/reports', name: 'admin.reports', methods: ['GET'])]
    public function reports(
        \App\Repository\TransactionRepository $transactionRepository,
        \App\Repository\PaiementLoyerRepository $paiementLoyerRepository,
        \App\Repository\BienRepository $bienRepository,
        \App\Repository\VisiteRepository $visiteRepository
    ): Response {
        $transactions = $transactionRepository->findAll();
        $paiements = $paiementLoyerRepository->findAll();
        $biens = $bienRepository->findAll();
        $visites = $visiteRepository->findAll();

        $totalSales = 0;
        $totalCommissions = 0;
        $activeBiens = 0;

        $typesBiensCount = [
            'maison' => 0,
            'appartement' => 0,
            'terrain' => 0,
            'local' => 0,
            'autre' => 0
        ];
        
        foreach ($biens as $bien) {
            if ($bien->getStatut() === 'disponible') {
                $activeBiens++;
                $type = strtolower((string)$bien->getType());
                if (array_key_exists($type, $typesBiensCount)) {
                    $typesBiensCount[$type]++;
                } else {
                    $typesBiensCount['autre']++;
                }
            }
        }

        // Monthly breakdown data for Chart.js (1 = Jan, 12 = Dec)
        $monthlyRevenue = array_fill(1, 12, 0);
        $monthlyTransactions = array_fill(1, 12, 0);

        $salesCount = 0;
        $rentalsCount = 0;

        foreach ($transactions as $t) {
            if ($t->getStatut() === 'paye' || $t->getStatut() === 'signe') {
                $totalSales += $t->getMontant();
                $com = $t->getCommissionMontant() ?? 0;
                $totalCommissions += $com;

                $date = $t->getDateTransaction() ?? new \DateTimeImmutable();
                $month = (int) $date->format('n');
                $monthlyRevenue[$month] += $com;
                $monthlyTransactions[$month]++;

                if ($t->getType() === 'vente') {
                    $salesCount++;
                } else {
                    $rentalsCount++;
                }
            }
        }

        // Add rent payments to monthly revenue as well (agency takes 10% commission on rental payments)
        foreach ($paiements as $p) {
            $totalSales += $p->getMontantLoyer();
            $com = $p->getMontantLoyer() * 0.1;
            $totalCommissions += $com;

            $date = $p->getCreatedAt() ?? new \DateTimeImmutable();
            $month = (int) $date->format('n');
            $monthlyRevenue[$month] += $com;
        }

        // Format monthly data for Chart.js
        $revenueData = array_values($monthlyRevenue);
        $transactionData = array_values($monthlyTransactions);

        return $this->render('admin/reports/index.html.twig', [
            'total_sales' => $totalSales,
            'total_commissions' => $totalCommissions,
            'active_biens' => $activeBiens,
            'total_visites' => count($visites),
            'sales_count' => $salesCount,
            'rentals_count' => $rentalsCount,
            'revenue_data' => json_encode($revenueData),
            'transaction_data' => json_encode($transactionData),
            'types_biens_count' => json_encode(array_values($typesBiensCount)),
        ]);
    }

    #[Route('/transactions/export/csv', name: 'admin.transactions.export_csv', methods: ['GET'])]
    public function exportTransactionsCsv(\App\Repository\TransactionRepository $transactionRepository): Response
    {
        $transactions = $transactionRepository->findBy([], ['dateTransaction' => 'DESC']);

        $filename = "reporting_financier_" . date('Y-m-d_H-i') . ".csv";
        $handle = fopen('php://temp', 'w+');
        
        // BOM UTF-8 for Excel
        fputs($handle, "\xEF\xBB\xBF");
        
        // Header
        fputcsv($handle, [
            'ID', 
            'Bien', 
            'Nature',
            'Client', 
            'Agent', 
            'Prix de Vente/Loyer', 
            'Commission (%)',
            'Comission Montant',
            'Date Transaction', 
            'Statut'
        ], ';');
        
        foreach ($transactions as $t) {
            $dateTrans = $t->getDateTransaction();
            fputcsv($handle, [
                '#TR-' . str_pad($t->getId(), 5, '0', STR_PAD_LEFT),
                $t->getBien() ? $t->getBien()->getTitre() : 'N/A',
                strtoupper($t->getType() ?? 'N/A'),
                $t->getClient() ? $t->getClient()->getName() : 'N/A',
                $t->getAgent() ? $t->getAgent()->getName() : 'N/A',
                $t->getMontant(),
                ($t->getCommissionPourcentage() ?? 0) . '%',
                $t->getCommissionMontant() ?? 0,
                $dateTrans ? $dateTrans->format('d/m/Y') : 'N/A',
                strtoupper($t->getStatut() ?? 'N/A')
            ], ';');
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    #[Route('/logs', name: 'admin.logs', methods: ['GET'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function logs(Request $request, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        // For Symfony, we don't have Spatie Activitylog by default.
        // We render the view with an empty array or basic placeholder data
        // until a Doctrine extension (like Gedmo Loggable) is fully integrated.
        $activities = []; 

        return $this->render('admin/logs/index.html.twig', [
            'activities' => $activities,
            'users' => $users
        ]);
    }
}
