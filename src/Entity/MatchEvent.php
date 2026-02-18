<?php

namespace App\Entity;

use App\Enum\MatchEventType;
use App\Repository\MatchEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatchEventRepository::class)]
class MatchEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GameMatch::class, inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'match_id', nullable: false)]
    private GameMatch $match;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\Column(length: 30, enumType: MatchEventType::class)]
    private MatchEventType $type;

    #[ORM\Column(nullable: true)]
    private ?int $minute = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $externalId = null;

    public function __construct(GameMatch $match, Team $team, Player $player, MatchEventType $type)
    {
        $this->match = $match;
        $this->team = $team;
        $this->player = $player;
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable();
        $match->getEvents()->add($this);
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

    public function getType(): MatchEventType
    {
        return $this->type;
    }

    public function setType(MatchEventType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMinute(): ?int
    {
        return $this->minute;
    }

    public function setMinute(?int $minute): static
    {
        $this->minute = $minute;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed>|null $metadata */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
