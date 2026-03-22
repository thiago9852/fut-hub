<?php

namespace App\Service\Statistics;

use App\Entity\Player;
use App\Entity\Team;

final class PlayerStat
{
    public int $count = 0;

    public function __construct(
        public readonly Player $player,
        public readonly Team $team,
    ) {
    }
}
