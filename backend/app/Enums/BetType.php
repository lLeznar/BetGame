<?php

namespace App\Enums;

enum BetType: string
{
    case Ante = 'ante';
    case Raise = 'raise';
    case Call = 'call';
    case Fold = 'fold';
}
