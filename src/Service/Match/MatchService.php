<?php

namespace App\Service\Match;

use App\Entity\GameMatch;
use App\Enum\MatchStatus;

/**
 * Ponto único de registro de resultado de uma partida.
 *
 * Centralizado aqui (em vez de setar os campos diretamente no controller)
 * porque a API (Fase 08) e a importação via Excel (Fase 10) também
 * precisarão registrar resultados com a mesma regra.
 */
class MatchService
{
    public function registerResult(GameMatch $match, int $homeScore, int $awayScore): void
    {
        if (MatchStatus::CANCELLED === $match->getStatus()) {
            throw new \DomainException('Não é possível registrar resultado de uma partida cancelada.');
        }

        if ($homeScore < 0 || $awayScore < 0) {
            throw new \InvalidArgumentException('O placar não pode ser negativo.');
        }

        $match->setHomeScore($homeScore);
        $match->setAwayScore($awayScore);
        $match->setStatus(MatchStatus::FINISHED);
    }
}
