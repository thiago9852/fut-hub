<?php

namespace App\Tests\Service\Statistics;

use App\Entity\Championship;
use App\Entity\GameMatch;
use App\Entity\MatchEvent;
use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Round;
use App\Entity\Season;
use App\Entity\Team;
use App\Enum\MatchEventType;
use App\Repository\MatchEventRepository;
use App\Service\Statistics\StatisticsService;
use PHPUnit\Framework\TestCase;

class StatisticsServiceTest extends TestCase
{
    private StatisticsService $statisticsService;
    private Organization $organization;
    private Season $season;
    private GameMatch $match;
    private Team $home;
    private Team $away;

    protected function setUp(): void
    {
        $this->statisticsService = new StatisticsService($this->createStub(MatchEventRepository::class));
        $this->organization = new Organization('Liga Teste', 'liga-teste');
        $championship = new Championship($this->organization, 'Campeonato Teste', 'campeonato-teste');
        $this->season = new Season($championship, '2026', 2026);
        $round = new Round($this->season, 1);
        $this->home = new Team($this->organization, 'Time A', 'time-a');
        $this->away = new Team($this->organization, 'Time B', 'time-b');
        $this->match = new GameMatch($this->season, $round, $this->home, $this->away);
    }

    private function createPlayer(string $name): Player
    {
        return new Player($this->organization, $name, strtolower(str_replace(' ', '-', $name)));
    }

    public function testArtilheiroEIdentificadoPeloNumeroDeGols(): void
    {
        $topScorer = $this->createPlayer('Artilheiro');
        $otherPlayer = $this->createPlayer('Outro Jogador');

        new MatchEvent($this->match, $this->home, $topScorer, MatchEventType::GOAL);
        new MatchEvent($this->match, $this->home, $topScorer, MatchEventType::GOAL);
        new MatchEvent($this->match, $this->away, $otherPlayer, MatchEventType::GOAL);

        $topScorers = $this->statisticsService->topScorers($this->season);

        self::assertSame($topScorer, $topScorers[0]->player);
        self::assertSame(2, $topScorers[0]->count);
        self::assertSame($otherPlayer, $topScorers[1]->player);
        self::assertSame(1, $topScorers[1]->count);
    }

    public function testGolContraNaoContaComoGolDoJogador(): void
    {
        $player = $this->createPlayer('Jogador');
        new MatchEvent($this->match, $this->home, $player, MatchEventType::OWN_GOAL);

        $topScorers = $this->statisticsService->topScorers($this->season);

        self::assertSame([], $topScorers);
    }

    public function testCartoesSaoContabilizadosPorTipo(): void
    {
        $player = $this->createPlayer('Jogador Indisciplinado');
        new MatchEvent($this->match, $this->home, $player, MatchEventType::YELLOW_CARD);
        new MatchEvent($this->match, $this->home, $player, MatchEventType::YELLOW_CARD);
        new MatchEvent($this->match, $this->home, $player, MatchEventType::RED_CARD);

        $yellowCards = $this->statisticsService->yellowCards($this->season);
        $redCards = $this->statisticsService->redCards($this->season);

        self::assertSame(2, $yellowCards[0]->count);
        self::assertSame(1, $redCards[0]->count);
    }

    public function testLimiteDeResultadosERespeitado(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $player = $this->createPlayer('Jogador '.$i);
            new MatchEvent($this->match, $this->home, $player, MatchEventType::GOAL);
        }

        $topScorers = $this->statisticsService->topScorers($this->season, 3);

        self::assertCount(3, $topScorers);
    }
}
