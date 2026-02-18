<?php

namespace App\Enum;

enum MatchEventType: string
{
    case GOAL = 'GOAL';
    case ASSIST = 'ASSIST';
    case YELLOW_CARD = 'YELLOW_CARD';
    case RED_CARD = 'RED_CARD';
    case SUBSTITUTION_IN = 'SUBSTITUTION_IN';
    case SUBSTITUTION_OUT = 'SUBSTITUTION_OUT';
    case OWN_GOAL = 'OWN_GOAL';
}
