<?php

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentVoter extends Voter
{
    public const VIEW_ANY = 'DOCUMENT_VIEW_ANY';
    public const VIEW = 'DOCUMENT_VIEW';
    public const DELETE = 'DOCUMENT_DELETE';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_ANY, self::VIEW, self::DELETE])) {
            return false;
        }

        if ($attribute !== self::VIEW_ANY && !$subject instanceof Document) {
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

        // Admin a tous les droits (hiérarchie respectée)
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW_ANY:
                // Agents peuvent voir la liste globale
                return $this->security->isGranted('ROLE_AGENT');
            case self::VIEW:
                return $this->canView($subject, $user);
            case self::DELETE:
                return $this->canDelete($subject, $user);
        }

        return false;
    }


    private function canView(Document $document, User $user): bool
    {
        if ($document->getUser() === $user) {
            return true;
        }

        $bien = $document->getBien();
        if ($bien && ($bien->getOwner() === $user || $bien->getAgent() === $user)) {
            return true;
        }

        $transaction = $document->getTransaction();
        if ($transaction && ($transaction->getClient() === $user || $transaction->getAgent() === $user)) {
            return true;
        }

        return false;
    }

    private function canDelete(Document $document, User $user): bool
    {
        return $document->getUser() === $user;
    }
}
