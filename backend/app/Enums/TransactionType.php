<?php

namespace App\Enums;

enum TransactionType: string
{
    case BuyIn = 'buy_in';
    case Payout = 'payout';
    case Ante = 'ante';
    case Bet = 'bet';
    case SideBetPayout = 'side_bet_payout';
    case Rollover = 'rollover';
}
