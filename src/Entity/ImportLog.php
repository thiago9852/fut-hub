<?php

namespace App\Entity;

use App\Repository\ImportLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportLogRepository::class)]
class ImportLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column]
    private int $recordsProcessed = 0;

    #[ORM\Column]
    private int $recordsCreated = 0;

    #[ORM\Column]
    private int $recordsUpdated = 0;

    #[ORM\Column]
    private int $recordsFailed = 0;

    /** @var array<int, string>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $errors = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Organization $organization, User $user, string $type, string $status = 'EM_ANDAMENTO')
    {
        $this->organization = $organization;
        $this->user = $user;
        $this->type = $type;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

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

    public function getRecordsProcessed(): int
    {
        return $this->recordsProcessed;
    }

    public function setRecordsProcessed(int $recordsProcessed): static
    {
        $this->recordsProcessed = $recordsProcessed;

        return $this;
    }

    public function getRecordsCreated(): int
    {
        return $this->recordsCreated;
    }

    public function setRecordsCreated(int $recordsCreated): static
    {
        $this->recordsCreated = $recordsCreated;

        return $this;
    }

    public function getRecordsUpdated(): int
    {
        return $this->recordsUpdated;
    }

    public function setRecordsUpdated(int $recordsUpdated): static
    {
        $this->recordsUpdated = $recordsUpdated;

        return $this;
    }

    public function getRecordsFailed(): int
    {
        return $this->recordsFailed;
    }

    public function setRecordsFailed(int $recordsFailed): static
    {
        $this->recordsFailed = $recordsFailed;

        return $this;
    }

    /** @return array<int, string>|null */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /** @param array<int, string>|null $errors */
    public function setErrors(?array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
