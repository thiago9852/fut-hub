<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Enum\MatchStatus;
use App\Repository\GameMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a match ("Match" in the README's data model).
 *
 * Renamed to GameMatch because `match` is a reserved keyword in PHP 8+
 * and cannot be used as a class name. The table is named `matches` for
 * the same reason (`MATCH` is also a reserved word in MySQL).
 */
#[ORM\Entity(repositoryClass: GameMatchRepository::class)]
#[ORM\Table(name: 'matches')]
class GameMatch
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    #[ORM\ManyToOne(targetEntity: Round::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: false)]
    private Round $round;

    #[ORM\ManyToOne(targetEntity: Stadium::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Stadium $stadium = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $homeTeam;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $awayTeam;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $homeScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $awayScore = null;

    #[ORM\Column(length: 20, enumType: MatchStatus::class)]
    private MatchStatus $status = MatchStatus::SCHEDULED;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $externalId = null;

    /** @var Collection<int, MatchEvent> */
    #[ORM\OneToMany(targetEntity: MatchEvent::class, mappedBy: 'match')]
    private Collection $events;

    /** @var Collection<int, MatchLineup> */
    #[ORM\OneToMany(targetEntity: MatchLineup::class, mappedBy: 'match')]
    private Collection $lineups;

    public function __construct(Season $season, Round $round, Team $homeTeam, Team $awayTeam)
    {
        $this->season = $season;
        $this->round = $round;
        $this->homeTeam = $homeTeam;
        $this->awayTeam = $awayTeam;
        $this->events = new ArrayCollection();
        $this->lineups = new ArrayCollection();
        $season->getMatches()->add($this);
        $round->getMatches()->add($this);
        $this->initializeTimestamps();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function getStadium(): ?Stadium
    {
        return $this->stadium;
    }

    public function setStadium(?Stadium $stadium): static
    {
        $this->stadium = $stadium;

        return $this;
    }

    public function getHomeTeam(): Team
    {
        return $this->homeTeam;
    }

    public function getAwayTeam(): Team
    {
        return $this->awayTeam;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getHomeScore(): ?int
    {
        return $this->homeScore;
    }

    public function setHomeScore(?int $homeScore): static
    {
        $this->homeScore = $homeScore;

        return $this;
    }

    public function getAwayScore(): ?int
    {
        return $this->awayScore;
    }

    public function setAwayScore(?int $awayScore): static
    {
        $this->awayScore = $awayScore;

        return $this;
    }

    public function getStatus(): MatchStatus
    {
        return $this->status;
    }

    public function setStatus(MatchStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return Collection<int, MatchEvent> */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    /** @return Collection<int, MatchLineup> */
    public function getLineups(): Collection
    {
        return $this->lineups;
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
