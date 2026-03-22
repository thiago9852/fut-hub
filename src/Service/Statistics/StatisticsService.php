<?php

namespace App\Service\Statistics;

use App\Entity\MatchEvent;
use App\Entity\Player;
use App\Entity\Season;
use App\Enum\MatchEventType;
use App\Repository\MatchEventRepository;

/**
 * Deriva estatísticas (artilharia, assistências, cartões) a partir do
 * histórico de eventos das partidas, em vez de manter contadores
 * redundantes — a fonte de verdade é sempre o MatchEvent.
 */
class StatisticsService
{
    public function __construct(
        private readonly MatchEventRepository $matchEventRepository,
    ) {
    }

    /** Total de um tipo de evento na carreira do jogador (todas as temporadas). */
    public function countPlayerEvents(Player $player, MatchEventType $type): int
    {
        return $this->matchEventRepository->count(['player' => $player, 'type' => $type]);
    }

    /** @return list<PlayerStat> ordenado do maior para o menor, limitado a $limit */
    public function topScorers(Season $season, int $limit = 10): array
    {
        return $this->rankByEventType($season, MatchEventType::GOAL, $limit);
    }

    /** @return list<PlayerStat> ordenado do maior para o menor, limitado a $limit */
    public function topAssists(Season $season, int $limit = 10): array
    {
        return $this->rankByEventType($season, MatchEventType::ASSIST, $limit);
    }

    /** @return list<PlayerStat> ordenado do maior para o menor, limitado a $limit */
    public function yellowCards(Season $season, int $limit = 10): array
    {
        return $this->rankByEventType($season, MatchEventType::YELLOW_CARD, $limit);
    }

    /** @return list<PlayerStat> ordenado do maior para o menor, limitado a $limit */
    public function redCards(Season $season, int $limit = 10): array
    {
        return $this->rankByEventType($season, MatchEventType::RED_CARD, $limit);
    }

    /** @return list<PlayerStat> */
    private function rankByEventType(Season $season, MatchEventType $type, int $limit): array
    {
        /** @var array<int, PlayerStat> $stats indexado por Player::getId() */
        $stats = [];

        foreach ($season->getMatches() as $match) {
            foreach ($match->getEvents() as $event) {
                if ($type !== $event->getType()) {
                    continue;
                }

                $this->increment($stats, $event);
            }
        }

        $ranked = array_values($stats);

        usort($ranked, fn (PlayerStat $a, PlayerStat $b) => $b->count <=> $a->count);

        return array_slice($ranked, 0, $limit);
    }

    /**
     * @param array<int, PlayerStat> $stats indexado por spl_object_id(), não por Player::getId() — este último é null para entidades ainda não persistidas
     */
    private function increment(array &$stats, MatchEvent $event): void
    {
        $playerId = spl_object_id($event->getPlayer());

        $stat = $stats[$playerId] ??= new PlayerStat($event->getPlayer(), $event->getTeam());
        $stat->count++;
    }
}
