<?php

namespace App\Entity;

// Laravel: app/Models/Transaction.php

use App\Repository\TransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bien $bien = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null; // user_id in Laravel

    #[ORM\ManyToOne(inversedBy: 'transactionsAsAgent')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $agent = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Visite $visite = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // vente, location

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $commissionPourcentage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $commissionMontant = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateTransaction = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFinOccupation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $isArchived = false;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $clientSigned = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $clientSignedAt = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $ownerSigned = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ownerSignedAt = null;

    #[ORM\Column(options: ["default" => false])]
    private ?bool $agencySigned = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $agencySignedAt = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $signatureIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clientSignatureImage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ownerSignatureImage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $agencySignatureImage = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: Document::class)]
    private Collection $documents;

    /**
     * @var Collection<int, PaiementLoyer>
     */
    #[ORM\OneToMany(mappedBy: 'transaction', targetEntity: PaiementLoyer::class)]
    private Collection $paiementsLoyer;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->paiementsLoyer = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBien(): ?Bien
    {
        return $this->bien;
    }

    public function setBien(?Bien $bien): static
    {
        $this->bien = $bien;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;
        return $this;
    }

    public function getVisite(): ?Visite
    {
        return $this->visite;
    }

    public function setVisite(?Visite $visite): static
    {
        $this->visite = $visite;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCommissionPourcentage(): ?string
    {
        return $this->commissionPourcentage;
    }

    public function setCommissionPourcentage(?string $commissionPourcentage): static
    {
        $this->commissionPourcentage = $commissionPourcentage;
        return $this;
    }

    public function getCommissionMontant(): ?string
    {
        return $this->commissionMontant;
    }

    public function setCommissionMontant(?string $commissionMontant): static
    {
        $this->commissionMontant = $commissionMontant;
        return $this;
    }

    public function getDateTransaction(): ?\DateTimeInterface
    {
        return $this->dateTransaction;
    }

    public function setDateTransaction(?\DateTimeInterface $dateTransaction): static
    {
        $this->dateTransaction = $dateTransaction;
        return $this;
    }

    public function getDateFinOccupation(): ?\DateTimeInterface
    {
        return $this->dateFinOccupation;
    }

    public function setDateFinOccupation(?\DateTimeInterface $dateFinOccupation): static
    {
        $this->dateFinOccupation = $dateFinOccupation;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function isClientSigned(): ?bool
    {
        return $this->clientSigned;
    }

    public function setClientSigned(bool $clientSigned): static
    {
        $this->clientSigned = $clientSigned;
        return $this;
    }

    public function getClientSignedAt(): ?\DateTimeInterface
    {
        return $this->clientSignedAt;
    }

    public function setClientSignedAt(?\DateTimeInterface $clientSignedAt): static
    {
        $this->clientSignedAt = $clientSignedAt;
        return $this;
    }

    public function isOwnerSigned(): ?bool
    {
        return $this->ownerSigned;
    }

    public function setOwnerSigned(bool $ownerSigned): static
    {
        $this->ownerSigned = $ownerSigned;
        return $this;
    }

    public function getOwnerSignedAt(): ?\DateTimeInterface
    {
        return $this->ownerSignedAt;
    }

    public function setOwnerSignedAt(?\DateTimeInterface $ownerSignedAt): static
    {
        $this->ownerSignedAt = $ownerSignedAt;
        return $this;
    }

    public function isAgencySigned(): ?bool
    {
        return $this->agencySigned;
    }

    public function setAgencySigned(bool $agencySigned): static
    {
        $this->agencySigned = $agencySigned;
        return $this;
    }

    public function getAgencySignedAt(): ?\DateTimeInterface
    {
        return $this->agencySignedAt;
    }

    public function setAgencySignedAt(?\DateTimeInterface $agencySignedAt): static
    {
        $this->agencySignedAt = $agencySignedAt;
        return $this;
    }

    public function getSignatureIp(): ?string
    {
        return $this->signatureIp;
    }

    public function setSignatureIp(?string $signatureIp): static
    {
        $this->signatureIp = $signatureIp;
        return $this;
    }

    public function getClientSignatureImage(): ?string
    {
        return $this->clientSignatureImage;
    }

    public function setClientSignatureImage(?string $clientSignatureImage): static
    {
        $this->clientSignatureImage = $clientSignatureImage;
        return $this;
    }

    public function getOwnerSignatureImage(): ?string
    {
        return $this->ownerSignatureImage;
    }

    public function setOwnerSignatureImage(?string $ownerSignatureImage): static
    {
        $this->ownerSignatureImage = $ownerSignatureImage;
        return $this;
    }

    public function getAgencySignatureImage(): ?string
    {
        return $this->agencySignatureImage;
    }

    public function setAgencySignatureImage(?string $agencySignatureImage): static
    {
        $this->agencySignatureImage = $agencySignatureImage;
        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    /**
     * @return Collection<int, PaiementLoyer>
     */
    public function getPaiementsLoyer(): Collection
    {
        return $this->paiementsLoyer;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
