<?php

namespace App\Tests\Service\Match;

use App\Entity\Championship;
use App\Entity\GameMatch;
use App\Entity\Organization;
use App\Entity\Round;
use App\Entity\Season;
use App\Entity\Team;
use App\Enum\MatchStatus;
use App\Service\Match\MatchService;
use PHPUnit\Framework\TestCase;

class MatchServiceTest extends TestCase
{
    private function createMatch(): GameMatch
    {
        $organization = new Organization('Liga Teste', 'liga-teste');
        $championship = new Championship($organization, 'Campeonato Teste', 'campeonato-teste');
        $season = new Season($championship, '2026', 2026);
        $round = new Round($season, 1);
        $home = new Team($organization, 'Time A', 'time-a');
        $away = new Team($organization, 'Time B', 'time-b');

        return new GameMatch($season, $round, $home, $away);
    }

    public function testRegistrarResultadoAtualizaPlacarEStatus(): void
    {
        $match = $this->createMatch();
        $service = new MatchService();

        $service->registerResult($match, 2, 1);

        self::assertSame(2, $match->getHomeScore());
        self::assertSame(1, $match->getAwayScore());
        self::assertSame(MatchStatus::FINISHED, $match->getStatus());
    }

    public function testNaoPermitePlacarNegativo(): void
    {
        $match = $this->createMatch();
        $service = new MatchService();

        $this->expectException(\InvalidArgumentException::class);

        $service->registerResult($match, -1, 0);
    }

    public function testNaoPermiteRegistrarResultadoEmPartidaCancelada(): void
    {
        $match = $this->createMatch();
        $match->setStatus(MatchStatus::CANCELLED);
        $service = new MatchService();

        $this->expectException(\DomainException::class);

        $service->registerResult($match, 1, 0);
    }
}
