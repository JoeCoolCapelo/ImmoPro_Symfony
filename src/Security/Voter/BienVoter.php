<?php

namespace App\Security\Voter;

use App\Entity\Bien;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BienVoter extends Voter
{
    public const VIEW_ANY = 'BIEN_VIEW_ANY';
    public const VIEW = 'BIEN_VIEW';
    public const CREATE = 'BIEN_CREATE';
    public const UPDATE = 'BIEN_UPDATE';
    public const DELETE = 'BIEN_DELETE';
    public const VALIDATE = 'BIEN_VALIDATE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_ANY, self::VIEW, self::CREATE, self::UPDATE, self::DELETE, self::VALIDATE])) {
            return false;
        }

        // CREATE and VIEW_ANY don't need a Bien subject
        if (in_array($attribute, [self::CREATE, self::VIEW_ANY])) {
            return true;
        }

        // All other attributes require a Bien instance
        if (!$subject instanceof Bien) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // L'admin a tous les droits sur les biens
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // L'agent peut voir et modifier tous les biens
        if ($this->security->isGranted('ROLE_AGENT')) {
            if (in_array($attribute, [self::VIEW_ANY, self::VIEW, self::UPDATE, self::VALIDATE])) {
                return true;
            }
        }

        switch ($attribute) {
            case self::VIEW_ANY:
                return true; // Everyone (filtered per role in controller)
            case self::VIEW:
                return $this->canView($subject, $user instanceof User ? $user : null);
            case self::CREATE:
                if (!$user instanceof User) return false;
                return $this->security->isGranted('ROLE_AGENT');
            case self::UPDATE:
                if (!$user instanceof User) return false;
                return $this->canUpdate($subject, $user);
            case self::DELETE:
                if (!$user instanceof User) return false;
                return $this->canDelete($subject, $user);
            case self::VALIDATE:
                return false;
        }

        return false;

    }

    private function canView(Bien $bien, ?User $user): bool
    {
        // Biens visibles publiquement pour les clients
        $statutsVisibles = ['publié', 'loué'];
        if (in_array($bien->getStatut(), $statutsVisibles)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Le propriétaire voit toujours ses propres biens, même suspendus
        if ($bien->getOwner() === $user) {
            return true;
        }

        if ($bien->getAgent() === $user) {
            return true;
        }

        return false;
    }

    private function canUpdate(Bien $bien, User $user): bool
    {
        // L'agent en charge ou le propriétaire peuvent modifier
        return $bien->getAgent() === $user || $bien->getOwner() === $user;
    }

    private function canDelete(Bien $bien, User $user): bool
    {
        if ($bien->getStatut() === 'loué') {
            return false;
        }
        // Seul le propriétaire du bien peut le supprimer (Admin géré en haut du Voter)
        return $bien->getOwner() === $user;
    }
}
