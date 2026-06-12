<?php

namespace App\Security\Voter;

use App\Entity\Visite;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class VisiteVoter extends Voter
{
    public const VIEW_ANY = 'VISITE_VIEW_ANY';
    public const VIEW = 'VISITE_VIEW';
    public const CREATE = 'VISITE_CREATE';
    public const UPDATE = 'VISITE_UPDATE';
    public const DELETE = 'VISITE_DELETE';
    public const VALIDATE = 'VISITE_VALIDATE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_ANY, self::VIEW, self::CREATE, self::UPDATE, self::DELETE, self::VALIDATE])) {
            return false;
        }

        if ($attribute !== self::VIEW_ANY && self::CREATE !== $attribute && !$subject instanceof Visite) {
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

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        // L'admin a tous les droits sur les visites
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // L'agent peut tout voir et tout gérer
        if ($this->security->isGranted('ROLE_AGENT')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW_ANY:
                return true;
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::CREATE:
                return $this->security->isGranted('ROLE_CLIENT');
            case self::UPDATE:
                return $this->canUpdate($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
            case self::VALIDATE:
                return $this->canValidate($subject, $user);
        }

        return false;
    }


    private function canView(Visite $visite, User $user): bool
    {
        return $visite->getClient() === $user 
            || ($visite->getBien() && $visite->getBien()->getAgent() === $user)
            || ($visite->getBien() && $visite->getBien()->getOwner() === $user);
    }

    private function canUpdate(Visite $visite, User $user): bool
    {
        // L'agent en charge du bien peut modifier la visite
        if ($visite->getBien() && $visite->getBien()->getAgent() === $user) {
            return true;
        }

        // Le client peut modifier sa propre visite (uniquement pour déclarer son intérêt)
        if ($visite->getClient() === $user) {
            return true;
        }

        return false;
    }

    private function canValidate(Visite $visite, User $user): bool
    {
        return $visite->getBien() && $visite->getBien()->getAgent() === $user;
    }

    private function canDelete(Visite $visite, User $user): bool
    {
        // L'agent en charge du bien peut supprimer la visite
        if ($visite->getBien() && $visite->getBien()->getAgent() === $user) {
            return true;
        }

        // Le client peut annuler sa propre visite si elle est en attente
        if ($visite->getClient() === $user && $visite->getStatut() === 'en_attente') {
            return true;
        }

        return false;
    }
}
