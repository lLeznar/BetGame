<?php

namespace App\Enums;

enum RoundPhase: string
{
    case PreBet = 'pre_bet';
    case Betting = 'betting';
    case Showdown = 'showdown';
    case Settled = 'settled';
}
