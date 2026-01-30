<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ChampionshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChampionshipRepository::class)]
class Championship
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

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $format = null;

    #[ORM\Column(length: 20)]
    private string $status = 'ATIVO';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    /**
     * Regras esportivas do campeonato (pontuação e critérios de desempate).
     * Não fica fixo no código para permitir formatos de competição diferentes.
     *
     * @var array{win_points: int, draw_points: int, loss_points: int, ranking: list<string>}
     */
    #[ORM\Column(type: 'json')]
    private array $rules;

    /** @var Collection<int, Season> */
    #[ORM\OneToMany(targetEntity: Season::class, mappedBy: 'championship')]
    private Collection $seasons;

    public function __construct(Organization $organization, string $name, string $slug)
    {
        $this->organization = $organization;
        $this->name = $name;
        $this->slug = $slug;
        $this->seasons = new ArrayCollection();
        $this->rules = self::defaultRules();
        $this->initializeTimestamps();
    }

    /** @return array{win_points: int, draw_points: int, loss_points: int, ranking: list<string>} */
    public static function defaultRules(): array
    {
        return [
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'ranking' => ['points', 'goal_difference', 'goals_for', 'wins'],
        ];
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): static
    {
        $this->format = $format;

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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    /** @return Collection<int, Season> */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    /** @return array{win_points: int, draw_points: int, loss_points: int, ranking: list<string>} */
    public function getRules(): array
    {
        return $this->rules;
    }

    /** @param array{win_points: int, draw_points: int, loss_points: int, ranking: list<string>} $rules */
    public function setRules(array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }
}
