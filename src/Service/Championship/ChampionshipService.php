<?php

namespace App\Service\Championship;

use App\Entity\Championship;
use App\Entity\Season;

class ChampionshipService
{
    /**
     * A temporada corrente de um campeonato é a de maior ano cadastrado.
     * Usado pelo site público e pelo dashboard para saber qual temporada exibir
     * quando o visitante não escolhe uma explicitamente.
     */
    public function getCurrentSeason(Championship $championship): ?Season
    {
        $current = null;

        foreach ($championship->getSeasons() as $season) {
            if (null === $current || $season->getYear() > $current->getYear()) {
                $current = $season;
            }
        }

        return $current;
    }
}
