<?php

namespace App\Services;

use App\Models\GamePlayer;
use App\Models\Round;
use App\Models\SideBet;
use App\Enums\SideBetType;

class SideBetService
{
    /**
     * Evaluate all side bets for a player's hand and write SideBet records.
     */
    public function evaluateAll(GamePlayer $player, Round $round): void
    {
        $hand = $player->hand;
        if (!$hand || count($hand) < 5) return;
        
        $settings = $player->game->settings ?? [];
        if (!($settings['side_bets_enabled'] ?? false)) return;
        
        $payouts = $settings['side_bet_payouts'] ?? [
            'black_pair' => 10,
            'one_eye' => 20,
            'aces' => 30
        ];
        
        $this->evaluateBlackPair($player, $round, $hand, $payouts['black_pair'] ?? 10);
        $this->evaluateOneEye($player, $round, $hand, $payouts['one_eye'] ?? 20);
        $this->evaluateAces($player, $round, $hand, $payouts['aces'] ?? 30);
    }
    
    private function evaluateBlackPair(GamePlayer $player, Round $round, array $hand, float $payout): void
    {
        // Check for a pair where both cards are Spades or Clubs
        $blackCardsByRank = [];
        
        foreach ($hand as $card) {
            if (in_array($card['suit'], ['spades', 'clubs'])) {
                $blackCardsByRank[$card['rank']][] = $card;
            }
        }
        
        $won = false;
        foreach ($blackCardsByRank as $rank => $cards) {
            if (count($cards) >= 2) {
                $won = true;
                break;
            }
        }
        
        $this->recordSideBet($player, $round, SideBetType::BlackPair, $won, $payout);
    }
    
    private function evaluateOneEye(GamePlayer $player, Round $round, array $hand, float $payout): void
    {
        // J♠, J♥, K♦ are the traditional one-eyed face cards
        $oneEyedCards = [
            ['rank' => 'J', 'suit' => 'spades'],
            ['rank' => 'J', 'suit' => 'hearts'],
            ['rank' => 'K', 'suit' => 'diamonds']
        ];
        
        $won = false;
        foreach ($hand as $card) {
            foreach ($oneEyedCards as $oneEyed) {
                if ($card['rank'] === $oneEyed['rank'] && $card['suit'] === $oneEyed['suit']) {
                    $won = true;
                    break 2;
                }
            }
        }
        
        $this->recordSideBet($player, $round, SideBetType::OneEye, $won, $payout);
    }
    
    private function evaluateAces(GamePlayer $player, Round $round, array $hand, float $payout): void
    {
        // At least one Ace
        $won = false;
        foreach ($hand as $card) {
            if ($card['rank'] === 'A') {
                $won = true;
                break;
            }
        }
        
        $this->recordSideBet($player, $round, SideBetType::Aces, $won, $payout);
    }
    
    private function recordSideBet(GamePlayer $player, Round $round, SideBetType $type, bool $won, float $payoutAmount): void
    {
        SideBet::create([
            'round_id' => $round->id,
            'game_player_id' => $player->id,
            'type' => $type,
            'won' => $won,
            'payout' => $won ? $payoutAmount : 0,
            'evaluated_at' => now()
        ]);
        
        // Note: Actual payout of funds to wallet_balance is handled in settleRound by GameService,
        // or immediately during startRound depending on exact business rules.
        // Project.md says "Evaluated and paid out the exact millisecond cards are dealt."
        // So we should pay it out here if won.
        
        if ($won) {
            $player->increment('wallet_balance', $payoutAmount);
            
            \App\Models\Transaction::create([
                'game_id' => $player->game_id,
                'to_player_id' => $player->id,
                'amount' => $payoutAmount,
                'type' => \App\Enums\TransactionType::SideBetPayout,
                'memo' => "Side Bet Won: {$type->value}"
            ]);
            
            // Broadcast SideBetResult event will be fired from GameService after evaluateAll returns.
        }
    }
}
