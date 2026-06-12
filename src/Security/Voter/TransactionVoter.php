<?php

namespace App\Security\Voter;

use App\Entity\Transaction;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TransactionVoter extends Voter
{
    public const VIEW_ANY = 'TRANSACTION_VIEW_ANY';
    public const VIEW = 'TRANSACTION_VIEW';
    public const CREATE = 'TRANSACTION_CREATE';
    public const UPDATE = 'TRANSACTION_UPDATE';
    public const DELETE = 'TRANSACTION_DELETE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_ANY, self::VIEW, self::CREATE, self::UPDATE, self::DELETE])) {
            return false;
        }

        if ($attribute !== self::VIEW_ANY && self::CREATE !== $attribute && !$subject instanceof Transaction) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Le Super Admin a tous les droits
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // L'admin a tous les droits sur les transactions
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW_ANY:
                // Client, Propriétaire et Agent peuvent voir la liste (filtrée dans le contrôleur)
                return $this->security->isGranted('ROLE_AGENT')
                    || $this->security->isGranted('ROLE_PROPRIETAIRE')
                    || $this->security->isGranted('ROLE_CLIENT');
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::CREATE:
                // Seul l'agent peut initier une transaction
                return $this->security->isGranted('ROLE_AGENT');
            case self::UPDATE:
                // Seul l'agent responsable de la transaction peut la modifier
                return $this->canUpdate($subject, $user);
            case self::DELETE:
                // Seul l'agent responsable (et le Super Admin) peut supprimer
                return $this->canUpdate($subject, $user);
        }

        return false;
    }

    private function canView(Transaction $transaction, User $user): bool
    {
        return $transaction->getClient() === $user 
            || $transaction->getAgent() === $user 
            || ($transaction->getBien() && $transaction->getBien()->getOwner() === $user);
    }

    private function canUpdate(Transaction $transaction, User $user): bool
    {
        return $transaction->getAgent() === $user;
    }
}
