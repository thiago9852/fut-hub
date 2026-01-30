<?php

namespace App\Entity;

use App\Repository\RoundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoundRepository::class)]
class Round
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'rounds')]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    #[ORM\Column]
    private int $number;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledDate = null;

    #[ORM\Column(length: 20)]
    private string $status = 'ATIVO';

    /** @var Collection<int, GameMatch> */
    #[ORM\OneToMany(targetEntity: GameMatch::class, mappedBy: 'round')]
    private Collection $matches;

    public function __construct(Season $season, int $number)
    {
        $this->season = $season;
        $this->number = $number;
        $this->matches = new ArrayCollection();
        $season->getRounds()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getScheduledDate(): ?\DateTimeImmutable
    {
        return $this->scheduledDate;
    }

    public function setScheduledDate(?\DateTimeImmutable $scheduledDate): static
    {
        $this->scheduledDate = $scheduledDate;

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

    /** @return Collection<int, GameMatch> */
    public function getMatches(): Collection
    {
        return $this->matches;
    }
}
