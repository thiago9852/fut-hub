<?php

namespace App\Entity;

use App\Repository\MatchLineupRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchLineupRepository::class)]
class MatchLineup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMatch::class, inversedBy: 'lineups')]
    #[ORM\JoinColumn(name: 'match_id', nullable: false)]
    private GameMatch $match;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\Column]
    private bool $starter = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(nullable: true)]
    private ?int $shirtNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $enteredAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $leftAt = null;

    public function __construct(GameMatch $match, Team $team, Player $player)
    {
        $this->match = $match;
        $this->team = $team;
        $this->player = $player;
        $match->getLineups()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatch(): GameMatch
    {
        return $this->match;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function isStarter(): bool
    {
        return $this->starter;
    }

    public function setStarter(bool $starter): static
    {
        $this->starter = $starter;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
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

    public function getEnteredAt(): ?int
    {
        return $this->enteredAt;
    }

    public function setEnteredAt(?int $enteredAt): static
    {
        $this->enteredAt = $enteredAt;

        return $this;
    }

    public function getLeftAt(): ?int
    {
        return $this->leftAt;
    }

    public function setLeftAt(?int $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }
}
