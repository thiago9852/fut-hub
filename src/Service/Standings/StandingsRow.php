<?php

namespace App\Service\Standings;

use App\Entity\Team;

final class StandingsRow
{
    public int $played = 0;
    public int $wins = 0;
    public int $draws = 0;
    public int $losses = 0;
    public int $goalsFor = 0;
    public int $goalsAgainst = 0;
    public int $points = 0;

    public function __construct(
        public readonly Team $team,
    ) {
    }

    public function getGoalDifference(): int
    {
        return $this->goalsFor - $this->goalsAgainst;
    }

    public function getWinPercentage(): float
    {
        if (0 === $this->played) {
            return 0.0;
        }

        return round(($this->points / ($this->played * 3)) * 100, 1);
    }
}
