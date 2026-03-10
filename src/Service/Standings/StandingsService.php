<?php

namespace App\Service\Standings;

use App\Entity\GameMatch;
use App\Entity\Season;
use App\Entity\Team;
use App\Enum\MatchStatus;

/**
 * Calcula a classificação de uma temporada a partir dos jogos encerrados.
 *
 * As regras de pontuação e os critérios de desempate vêm do campeonato
 * (Championship::getRules()) em vez de ficarem fixos aqui, para permitir
 * formatos de competição diferentes sem alterar código.
 */
class StandingsService
{
    /**
     * @return list<StandingsRow> ordenado da 1ª à última colocação
     */
    public function calculate(Season $season): array
    {
        $rules = $season->getChampionship()->getRules();

        /** @var array<int, StandingsRow> $rows indexado por spl_object_id(), não por Team::getId() — este último é null para entidades ainda não persistidas */
        $rows = [];

        $getRow = function (Team $team) use (&$rows): StandingsRow {
            $id = spl_object_id($team);

            return $rows[$id] ??= new StandingsRow($team);
        };

        foreach ($season->getMatches() as $match) {
            if (MatchStatus::FINISHED !== $match->getStatus()) {
                continue;
            }

            $this->applyMatch($getRow($match->getHomeTeam()), $getRow($match->getAwayTeam()), $match, $rules);
        }

        $standings = array_values($rows);

        usort($standings, fn (StandingsRow $a, StandingsRow $b) => $this->compare($a, $b, $rules['ranking']));

        return $standings;
    }

    /** @param array{win_points: int, draw_points: int, loss_points: int, ranking: list<string>} $rules */
    private function applyMatch(StandingsRow $home, StandingsRow $away, GameMatch $match, array $rules): void
    {
        $homeScore = $match->getHomeScore() ?? 0;
        $awayScore = $match->getAwayScore() ?? 0;

        $home->played++;
        $away->played++;
        $home->goalsFor += $homeScore;
        $home->goalsAgainst += $awayScore;
        $away->goalsFor += $awayScore;
        $away->goalsAgainst += $homeScore;

        if ($homeScore > $awayScore) {
            $home->wins++;
            $home->points += $rules['win_points'];
            $away->losses++;
            $away->points += $rules['loss_points'];
        } elseif ($awayScore > $homeScore) {
            $away->wins++;
            $away->points += $rules['win_points'];
            $home->losses++;
            $home->points += $rules['loss_points'];
        } else {
            $home->draws++;
            $away->draws++;
            $home->points += $rules['draw_points'];
            $away->points += $rules['draw_points'];
        }
    }

    /** @param list<string> $ranking */
    private function compare(StandingsRow $a, StandingsRow $b, array $ranking): int
    {
        foreach ($ranking as $criterion) {
            $result = match ($criterion) {
                'points' => $b->points <=> $a->points,
                'goal_difference' => $b->getGoalDifference() <=> $a->getGoalDifference(),
                'goals_for' => $b->goalsFor <=> $a->goalsFor,
                'wins' => $b->wins <=> $a->wins,
                default => 0,
            };

            if (0 !== $result) {
                return $result;
            }
        }

        return $a->team->getName() <=> $b->team->getName();
    }
}
