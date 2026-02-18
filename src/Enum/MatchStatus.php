<?php

namespace App\Enum;

enum MatchStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case LIVE = 'LIVE';
    case FINISHED = 'FINISHED';
    case POSTPONED = 'POSTPONED';
    case CANCELLED = 'CANCELLED';
}
