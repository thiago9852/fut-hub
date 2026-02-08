<?php

namespace App\Entity;

use App\Repository\TeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamPlayerRepository::class)]
class TeamPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'teamPlayers')]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    #[ORM\Column(nullable: true)]
    private ?int $shirtNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'ATIVO';

    public function __construct(Team $team, Player $player, Season $season)
    {
        $this->team = $team;
        $this->player = $player;
        $this->season = $season;
        $team->getTeamPlayers()->add($this);
        $player->getTeamPlayers()->add($this);
        $season->getTeamPlayers()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    public function setShirtNumber(?int $shirtNumber): static
    {
        $this->shirtNumber = $shirtNumber;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

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
}
