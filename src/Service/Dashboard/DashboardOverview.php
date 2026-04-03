<?php

namespace App\Service\Dashboard;

use App\Entity\Championship;
use App\Entity\GameMatch;
use App\Entity\ImportLog;
use App\Entity\Season;
use App\Service\Standings\StandingsRow;
use App\Service\Statistics\PlayerStat;

final class DashboardOverview
{
    public int $championshipsCount = 0;
    public int $seasonsCount = 0;
    public int $teamsCount = 0;
    public int $playersCount = 0;
    public int $matchesCount = 0;
    public int $goalsCount = 0;

    public ?Championship $championship = null;
    public ?Season $season = null;

    /** @var list<StandingsRow> */
    public array $standings = [];

    /** @var list<PlayerStat> */
    public array $topScorers = [];

    /** @var list<GameMatch> */
    public array $recentResults = [];

    /** @var list<GameMatch> */
    public array $upcomingMatches = [];

    /** @var list<array{label: string, goals: int}> */
    public array $goalsByRound = [];

    /** @var list<array{team: string, points: int}> */
    public array $pointsByTeam = [];

    /** @var list<ImportLog> */
    public array $recentImports = [];
}
