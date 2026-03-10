<?php

namespace App\Tests\Service\Standings;

use App\Entity\Championship;
use App\Entity\GameMatch;
use App\Entity\Organization;
use App\Entity\Round;
use App\Entity\Season;
use App\Entity\Team;
use App\Enum\MatchStatus;
use App\Service\Standings\StandingsService;
use PHPUnit\Framework\TestCase;

class StandingsServiceTest extends TestCase
{
    private StandingsService $standingsService;
    private Organization $organization;
    private Championship $championship;
    private Season $season;
    private Round $round;

    protected function setUp(): void
    {
        $this->standingsService = new StandingsService();
        $this->organization = new Organization('Liga Teste', 'liga-teste');
        $this->championship = new Championship($this->organization, 'Campeonato Teste', 'campeonato-teste');
        $this->season = new Season($this->championship, '2026', 2026);
        $this->round = new Round($this->season, 1);
    }

    private function createTeam(string $name): Team
    {
        return new Team($this->organization, $name, strtolower($name));
    }

    private function finishMatch(Team $home, Team $away, int $homeScore, int $awayScore): void
    {
        $match = new GameMatch($this->season, $this->round, $home, $away);
        $match->setHomeScore($homeScore);
        $match->setAwayScore($awayScore);
        $match->setStatus(MatchStatus::FINISHED);
    }

    public function testVitoriaGeraTresPontos(): void
    {
        $home = $this->createTeam('Time A');
        $away = $this->createTeam('Time B');
        $this->finishMatch($home, $away, 2, 0);

        $standings = $this->standingsService->calculate($this->season);

        $winner = $this->findRow($standings, $home);
        self::assertSame(3, $winner->points);
        self::assertSame(1, $winner->wins);
        self::assertSame(0, $winner->draws);
        self::assertSame(0, $winner->losses);
    }

    public function testEmpateGeraUmPonto(): void
    {
        $home = $this->createTeam('Time A');
        $away = $this->createTeam('Time B');
        $this->finishMatch($home, $away, 1, 1);

        $standings = $this->standingsService->calculate($this->season);

        self::assertSame(1, $this->findRow($standings, $home)->points);
        self::assertSame(1, $this->findRow($standings, $away)->points);
    }

    public function testDerrotaGeraZeroPontos(): void
    {
        $home = $this->createTeam('Time A');
        $away = $this->createTeam('Time B');
        $this->finishMatch($home, $away, 0, 3);

        $standings = $this->standingsService->calculate($this->season);

        $loser = $this->findRow($standings, $home);
        self::assertSame(0, $loser->points);
        self::assertSame(1, $loser->losses);
    }

    public function testSaldoDeGolsECalculadoCorretamente(): void
    {
        $home = $this->createTeam('Time A');
        $away = $this->createTeam('Time B');
        $this->finishMatch($home, $away, 4, 1);

        $standings = $this->standingsService->calculate($this->season);

        self::assertSame(3, $this->findRow($standings, $home)->getGoalDifference());
        self::assertSame(-3, $this->findRow($standings, $away)->getGoalDifference());
    }

    public function testJogosNaoEncerradosNaoEntramNaClassificacao(): void
    {
        $home = $this->createTeam('Time A');
        $away = $this->createTeam('Time B');
        new GameMatch($this->season, $this->round, $home, $away);

        $standings = $this->standingsService->calculate($this->season);

        self::assertSame([], $standings);
    }

    public function testClassificacaoOrdenaPorPontosDepoisSaldoDeGols(): void
    {
        $a = $this->createTeam('Time A');
        $b = $this->createTeam('Time B');
        $c = $this->createTeam('Time C');

        // A: 1 vitória (3 pts, saldo +3)
        $this->finishMatch($a, $c, 3, 0);
        // B: 1 vitória (3 pts, saldo +1) — empatado em pontos com A, mas saldo pior
        $this->finishMatch($b, $c, 1, 0);

        $standings = $this->standingsService->calculate($this->season);

        self::assertSame($a, $standings[0]->team);
        self::assertSame($b, $standings[1]->team);
    }

    /** @param list<\App\Service\Standings\StandingsRow> $standings */
    private function findRow(array $standings, Team $team): \App\Service\Standings\StandingsRow
    {
        foreach ($standings as $row) {
            if ($row->team === $team) {
                return $row;
            }
        }

        self::fail(sprintf('Nenhuma linha de classificação encontrada para o time %s.', $team->getName()));
    }
}
