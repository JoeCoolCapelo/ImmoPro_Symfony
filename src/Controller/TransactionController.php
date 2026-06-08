<?php

namespace App\Controller;

use App\Entity\Bien;
use App\Entity\Document;
use App\Entity\PaiementLoyer;
use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Repository\BienRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transactions')]
class TransactionController extends AbstractController
{
    #[Route('/', name: 'transactions.index', methods: ['GET'])]
    public function index(Request $request, TransactionRepository $transactionRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('TRANSACTION_VIEW_ANY');

        $qb = $transactionRepository->createQueryBuilder('t')
            ->leftJoin('t.bien', 'b')
            ->orderBy('t.createdAt', 'DESC');

        if ($search = $request->query->get('search')) {
            if (str_starts_with(strtoupper($search), '#TR-')) {
                $qb->andWhere('t.id = :searchId')
                   ->setParameter('searchId', (int) substr($search, 4));
            } elseif (str_starts_with($search, '#')) {
                $qb->andWhere('t.bien = :searchId')
                   ->setParameter('searchId', (int) substr($search, 1));
            } else {
                $qb->andWhere('t.bien LIKE :search OR t.id LIKE :search OR b.titre LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }
        }

        $type = $request->query->get('type');
        if ($type && in_array($type, ['vente', 'location'])) {
            $qb->andWhere('t.type = :type')
               ->setParameter('type', $type);
        }

        $user = $this->getUser();
        if ($this->isGranted('ROLE_CLIENT')) {
            $qb->andWhere('t.client = :user')
               ->setParameter('user', $user);
        } elseif ($this->isGranted('ROLE_PROPRIETAIRE')) {
            $qb->andWhere('b.owner = :user')
               ->setParameter('user', $user);
        } elseif ($this->isGranted('ROLE_AGENT')) {
            $qb->andWhere('t.agent = :user')
               ->setParameter('user', $user);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('t.isArchived = false');
        }

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('transactions/index.html.twig', [
            'transactions' => $pagination,
        ]);
    }

    #[Route('/{id}/archive', name: 'transactions.archive', methods: ['POST'])]
    #[IsGranted('TRANSACTION_UPDATE', subject: 'transaction')]
    public function archive(Transaction $transaction, EntityManagerInterface $em): Response
    {
        $transaction->setIsArchived(!$transaction->isArchived());
        $em->flush();

        $status = $transaction->isArchived() ? 'archivée' : 'désarchivée';
        $this->addFlash('success', "La transaction a été {$status} avec succès.");

        return $this->redirect($this->generateUrl('transactions.index'));
    }

    #[Route('/{id}/liberer', name: 'transactions.liberer', methods: ['POST'])]
    #[IsGranted('TRANSACTION_UPDATE', subject: 'transaction')]
    public function libererLocation(Transaction $transaction, EntityManagerInterface $em): Response
    {
        if ($transaction->getType() !== 'location') {
            $this->addFlash('error', 'Cette action n\'est possible que pour les locations.');
            return $this->redirect($this->generateUrl('transactions.index'));
        }

        $transaction->setIsArchived(true);
        $transaction->setDateFinOccupation(new \DateTime());
        $transaction->setStatut('clôturée');

        $bien = $transaction->getBien();
        if ($bien) {
            $bien->setStatut('en_attente');
        }

        $em->flush();

        $this->addFlash('success', 'Le bien a été libéré et la location archivée.');
        return $this->redirect($this->generateUrl('transactions.index'));
    }

    #[Route('/create', name: 'transactions.create', methods: ['GET', 'POST'])]
    #[IsGranted('TRANSACTION_CREATE')]
    public function create(Request $request, EntityManagerInterface $em, BienRepository $bienRepository, UserRepository $userRepository, NotificationService $notificationService): Response
    {
        $bienId = $request->query->get('bien_id');
        $bien = $bienId ? $bienRepository->find($bienId) : null;
        
        $transaction = new Transaction();
        if ($bien) {
            $transaction->setBien($bien);
        }

        // Get pre-filled client and visite
        $clientId = $request->query->get('user_id');
        if ($clientId) {
            $transaction->setClient($userRepository->find($clientId));
        }

        $visiteId = $request->query->get('visite_id');
        if ($visiteId) {
            $visite = $em->getRepository(\App\Entity\Visite::class)->find($visiteId);
            if ($visite) {
                $transaction->setVisite($visite);
            }
        }

        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bien = $transaction->getBien();
            $commissionPourcentage = $transaction->getCommissionPourcentage() ?? 10.00;
            $montant = $transaction->getMontant();
            $commissionMontant = ($montant * $commissionPourcentage) / 100;

            $transaction->setAgent($this->getUser());
            $transaction->setType($bien->getNature());
            $transaction->setCommissionMontant($commissionMontant);
            $transaction->setStatut('validée');

            $em->persist($transaction);

            // Handle documents
            $documents = $form->get('documents')->getData();
            if ($documents) {
                foreach ($documents as $file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$file->guessExtension();
                    
                    $file->move($this->getParameter('documents_directory').'/transactions', $newFilename);

                    $doc = new Document();
                    $doc->setTransaction($transaction);
                    $doc->setUser($this->getUser());
                    $doc->setTitre($file->getClientOriginalName());
                    $doc->setPath('documents/transactions/'.$newFilename);
                    $doc->setType('transaction_doc');
                    $em->persist($doc);
                }
            }

            $bien->setStatut($bien->getNature() === 'vente' ? 'vendu' : 'loué');

            $visite = $transaction->getVisite();
            if ($visite) {
                $visite->setStatut('finalisée');
            }

            if ($bien->getNature() === 'location') {
                $typePaiement = $form->get('typeLocationPaiement')->getData();
                $isChequeAgent = $form->get('chequeAgent')->getData() === 'oui';

                $nbMois = ($typePaiement === 'annuel') ? 12 : 1;
                $dateBase = new \DateTime('first day of this month');

                for ($i = 0; $i < $nbMois; $i++) {
                    $paiement = new PaiementLoyer();
                    $paiement->setTransaction($transaction);
                    $paiement->setBien($bien);
                    $paiement->setLocataire($transaction->getClient());
                    $paiement->setAgent($transaction->getAgent());
                    $paiement->setMontantLoyer($montant);
                    $paiement->setCommissionPourcentage($commissionPourcentage);
                    $paiement->setCommissionMontant(($montant * $commissionPourcentage) / 100);
                    
                    $dateEcheance = clone $dateBase;
                    if ($i > 0) {
                        $dateEcheance->modify("+$i months");
                    }
                    $paiement->setDateEcheance($dateEcheance);

                    if ($isChequeAgent) {
                        $paiement->setStatut('payé');
                        $paiement->setDatePaiement(new \DateTime());
                        $paiement->setCommentaire('Payé par chèque (Encaissé par l\'agent)');
                    } else {
                        $paiement->setStatut('en_attente');
                    }

                    $em->persist($paiement);
                }
            }

            $em->flush();

            // Notifier le client
            $notificationService->sendTransactionConfirmation($transaction);

            $this->addFlash('success', 'La transaction a été enregistrée avec succès.');
            return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
        }

        // Equivalent de: User::role('client')->get()
        $clients = []; // TODO: query clients

        return $this->render('transactions/create.html.twig', [
            'form' => $form->createView(),
            'bien' => $bien,
            'clients' => $clients,
        ]);
    }

    #[Route('/{id}', name: 'transactions.show', methods: ['GET'])]
    #[IsGranted('TRANSACTION_VIEW', subject: 'transaction')]
    public function show(Transaction $transaction): Response
    {
        $latestPaidPayment = null;
        if ($transaction->getType() === 'location') {
            foreach ($transaction->getPaiementsLoyer() as $p) {
                if ($p->getStatut() === 'payé') {
                    if (!$latestPaidPayment || $p->getDateEcheance() > $latestPaidPayment->getDateEcheance()) {
                        $latestPaidPayment = $p;
                    }
                }
            }
        }

        return $this->render('transactions/show.html.twig', [
            'transaction' => $transaction,
            'latestPaidPayment' => $latestPaidPayment,
        ]);
    }

    #[Route('/{id}/sign', name: 'transactions.sign', methods: ['POST'])]
    public function sign(Request $request, Transaction $transaction, EntityManagerInterface $em): Response
    {
        $signatureData = $request->request->get('signature_data');
        if (!$signatureData) {
            $this->addFlash('error', 'Signature requise.');
            return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
        }

        $user = $this->getUser();
        
        if ($user === $transaction->getClient()) {
            if ($transaction->isClientSigned()) {
                $this->addFlash('error', 'Vous avez déjà signé cette transaction.');
                return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
            }
            $transaction->setClientSigned(true);
            $transaction->setClientSignedAt(new \DateTime());
            $transaction->setSignatureIp($request->getClientIp());
            $transaction->setClientSignatureImage($signatureData);
        } elseif ($transaction->getBien() && $user === $transaction->getBien()->getOwner()) {
            if ($transaction->isOwnerSigned()) {
                $this->addFlash('error', 'Vous avez déjà signé cette transaction.');
                return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
            }
            $transaction->setOwnerSigned(true);
            $transaction->setOwnerSignedAt(new \DateTime());
            $transaction->setSignatureIp($request->getClientIp());
            $transaction->setOwnerSignatureImage($signatureData);
        } elseif ($this->isGranted('ROLE_SUPER_ADMIN') || ($this->isGranted('ROLE_AGENT') && $transaction->getAgent() === $user)) {
            if ($transaction->isAgencySigned()) {
                $this->addFlash('error', 'L\'agence a déjà signé cette transaction.');
                return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
            }
            $transaction->setAgencySigned(true);
            $transaction->setAgencySignedAt(new \DateTime());
            $transaction->setSignatureIp($request->getClientIp());
            $transaction->setAgencySignatureImage($signatureData);
        } else {
            throw $this->createAccessDeniedException();
        }

        $em->flush();
        $this->addFlash('success', 'Votre accord formel a été enregistré avec succès.');

        return $this->redirectToRoute('transactions.show', ['id' => $transaction->getId()]);
    }
}
