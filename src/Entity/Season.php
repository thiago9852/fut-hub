<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Championship::class, inversedBy: 'seasons')]
    #[ORM\JoinColumn(nullable: false)]
    private Championship $championship;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private int $year;

    #[ORM\Column(length: 20)]
    private string $status = 'ATIVO';

    /** @var Collection<int, Round> */
    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'season')]
    private Collection $rounds;

    /** @var Collection<int, GameMatch> */
    #[ORM\OneToMany(targetEntity: GameMatch::class, mappedBy: 'season')]
    private Collection $matches;

    /** @var Collection<int, TeamPlayer> */
    #[ORM\OneToMany(targetEntity: TeamPlayer::class, mappedBy: 'season')]
    private Collection $teamPlayers;

    public function __construct(Championship $championship, string $name, int $year)
    {
        $this->championship = $championship;
        $this->name = $name;
        $this->year = $year;
        $this->rounds = new ArrayCollection();
        $this->matches = new ArrayCollection();
        $championship->getSeasons()->add($this);
        $this->teamPlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChampionship(): Championship
    {
        return $this->championship;
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

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

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

    /** @return Collection<int, Round> */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    /** @return Collection<int, GameMatch> */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    /** @return Collection<int, TeamPlayer> */
    public function getTeamPlayers(): Collection
    {
        return $this->teamPlayers;
    }
}
