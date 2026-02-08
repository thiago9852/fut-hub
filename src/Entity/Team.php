<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\UniqueConstraint(columns: ['organization_id', 'external_id'])]
class Team
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $shortName = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $foundedAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'ATIVO';

    /**
     * Identificador de origem (ex.: da planilha Excel), usado pelo
     * ImportService para tornar a sincronização idempotente — ver README
     * "Idempotência".
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $externalId = null;

    /** @var Collection<int, TeamPlayer> */
    #[ORM\OneToMany(targetEntity: TeamPlayer::class, mappedBy: 'team')]
    private Collection $teamPlayers;

    public function __construct(Organization $organization, string $name, string $slug)
    {
        $this->organization = $organization;
        $this->name = $name;
        $this->slug = $slug;
        $this->teamPlayers = new ArrayCollection();
        $this->initializeTimestamps();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(?string $shortName): static
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getFoundedAt(): ?\DateTimeImmutable
    {
        return $this->foundedAt;
    }

    public function setFoundedAt(?\DateTimeImmutable $foundedAt): static
    {
        $this->foundedAt = $foundedAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return Collection<int, TeamPlayer> */
    public function getTeamPlayers(): Collection
    {
        return $this->teamPlayers;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }
}
