<?php

namespace App\Enums;

enum HandRank: int
{
    case HighCard = 0;
    case OnePair = 1;
    case TwoPair = 2;
    case ThreeOfAKind = 3;
    case Straight = 4;
    case Flush = 5;
    case FullHouse = 6;
    case FourOfAKind = 7;
    case StraightFlush = 8;
    case RoyalFlush = 9;
}
