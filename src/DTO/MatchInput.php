<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MatchInput
{
    #[Assert\NotBlank]
    public ?int $seasonId = null;

    #[Assert\NotBlank]
    public ?int $roundId = null;

    public ?int $stadiumId = null;

    #[Assert\NotBlank]
    public ?int $homeTeamId = null;

    #[Assert\NotBlank]
    public ?int $awayTeamId = null;

    #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
    public ?string $scheduledAt = null;

    #[Assert\PositiveOrZero]
    public ?int $homeScore = null;

    #[Assert\PositiveOrZero]
    public ?int $awayScore = null;

    #[Assert\Choice(choices: ['SCHEDULED', 'LIVE', 'FINISHED', 'POSTPONED', 'CANCELLED'])]
    public string $status = 'SCHEDULED';
}
