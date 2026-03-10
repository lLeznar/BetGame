<?php

namespace App\Enums;

enum GamePhase: string
{
    case Waiting = 'waiting';
    case Dealing = 'dealing';
    case Betting = 'betting';
    case Showdown = 'showdown';
    case Finished = 'finished';
}
