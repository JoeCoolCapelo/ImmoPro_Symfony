<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\PaiementLoyer;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class NotificationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $em;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $em)
    {
        $this->mailer = $mailer;
        $this->em = $em;
    }

    /**
     * Send email and create in-app notification for a transaction confirmation
     */
    public function sendTransactionConfirmation(Transaction $transaction): void
    {
        $client = $transaction->getClient();
        
        // 1. Send Email
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@immopro.gn', 'ImmoPro'))
                ->to($client->getEmail())
                ->subject('Confirmation de votre transaction')
                ->htmlTemplate('emails/transaction_confirmation.html.twig')
                ->context([
                    'transaction' => $transaction,
                    'user' => $client,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log or ignore mail failure in development
        }

        // 2. Save In-App Notification
        $bien = $transaction->getBien();
        $this->notify(
            $client,
            'Nouvelle transaction enregistrée',
            sprintf(
                'Votre transaction concernant le bien "%s" a été validée avec succès pour un montant de %s GNF.',
                $bien ? $bien->getTitre() : 'Bien immobilier',
                number_format($transaction->getMontant(), 0, ',', ' ')
            ),
            'transaction',
            $transaction->getId()
        );
    }

    /**
     * Send email and create in-app notification for rent payment received
     */
    public function sendPaiementLoyerRecu(PaiementLoyer $paiement): void
    {
        $locataire = $paiement->getLocataire();
        
        // 1. Send Email
        try {
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@immopro.gn', 'ImmoPro'))
                ->to($locataire->getEmail())
                ->subject('Quittance de loyer reçue')
                ->htmlTemplate('emails/paiement_recu.html.twig')
                ->context([
                    'paiement' => $paiement,
                    'user' => $locataire,
                ]);

            $this->mailer->send($email);
        } catch (\Exception $e) {
            // Log or ignore mail failure in development
        }

        // 2. Save In-App Notification
        $bien = $paiement->getBien();
        $this->notify(
            $locataire,
            'Paiement de loyer reçu',
            sprintf(
                'Votre paiement de %s GNF pour le loyer du bien "%s" a été enregistré avec succès.',
                number_format($paiement->getMontantLoyer(), 0, ',', ' '),
                $bien ? $bien->getTitre() : 'Bien immobilier'
            ),
            'paiement',
            $paiement->getId()
        );
    }

    /**
     * Helper to easily trigger database notifications in the application
     */
    public function notify(User $user, string $title, string $message, string $type = 'general', ?int $relatedId = null): Notification
    {
        $notification = new Notification();
        $notification->setUser($user)
            ->setTitle($title)
            ->setMessage($message)
            ->setType($type)
            ->setRelatedId($relatedId);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }
}
