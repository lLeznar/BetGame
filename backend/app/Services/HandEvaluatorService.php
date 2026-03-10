<?php

namespace App\Services;

use App\Enums\HandRank;

class HandEvaluatorService
{
    /**
     * Evaluate a 5-card hand
     * 
     * @param array $hand Array of 5 cards: ['suit' => 'spades', 'rank' => 'A', 'value' => 14]
     * @return array {rank: HandRank, highCards: array, label: string}
     */
    public function evaluate(array $hand): array
    {
        // Sort hand by value descending
        usort($hand, fn($a, $b) => $b['value'] <=> $a['value']);

        $values = array_column($hand, 'value');
        $suits = array_column($hand, 'suit');
        
        $isFlush = count(array_unique($suits)) === 1;
        $isStraight = $this->isStraight($values);
        
        $counts = array_count_values($values);
        arsort($counts); // Sort by frequency descending, then value (stable sort implicitly handled if we array_keys)
        
        // Custom sort for counts to ensure highest value of same frequency is first
        uksort($counts, function($a, $b) use ($counts) {
            if ($counts[$a] === $counts[$b]) {
                return $b <=> $a;
            }
            return $counts[$b] <=> $counts[$a];
        });

        $frequencies = array_values($counts);
        $cardValues = array_keys($counts); // These are the high cards in order of importance

        // Royal Flush
        if ($isFlush && $isStraight && $values[0] === 14) {
            return ['rank' => HandRank::RoyalFlush, 'highCards' => $cardValues, 'label' => 'Royal Flush'];
        }
        
        // Straight Flush
        if ($isFlush && $isStraight) {
            return ['rank' => HandRank::StraightFlush, 'highCards' => $cardValues, 'label' => 'Straight Flush'];
        }
        
        // Four of a Kind
        if ($frequencies[0] === 4) {
            return ['rank' => HandRank::FourOfAKind, 'highCards' => $cardValues, 'label' => 'Four of a Kind'];
        }
        
        // Full House
        if ($frequencies[0] === 3 && $frequencies[1] === 2) {
            return ['rank' => HandRank::FullHouse, 'highCards' => $cardValues, 'label' => 'Full House'];
        }
        
        // Flush
        if ($isFlush) {
            return ['rank' => HandRank::Flush, 'highCards' => $cardValues, 'label' => 'Flush'];
        }
        
        // Straight
        if ($isStraight) {
            return ['rank' => HandRank::Straight, 'highCards' => $cardValues, 'label' => 'Straight'];
        }
        
        // Three of a Kind
        if ($frequencies[0] === 3) {
            return ['rank' => HandRank::ThreeOfAKind, 'highCards' => $cardValues, 'label' => 'Three of a Kind'];
        }
        
        // Two Pair
        if ($frequencies[0] === 2 && $frequencies[1] === 2) {
            return ['rank' => HandRank::TwoPair, 'highCards' => $cardValues, 'label' => 'Two Pair'];
        }
        
        // One Pair
        if ($frequencies[0] === 2) {
            return ['rank' => HandRank::OnePair, 'highCards' => $cardValues, 'label' => 'One Pair'];
        }
        
        // High Card
        return ['rank' => HandRank::HighCard, 'highCards' => $cardValues, 'label' => 'High Card'];
    }

    /**
     * Check if values represent a straight (including A-2-3-4-5)
     */
    private function isStraight(array $values): bool
    {
        // Check normal straight
        $isNormalStraight = true;
        for ($i = 0; $i < 4; $i++) {
            if ($values[$i] - 1 !== $values[$i + 1]) {
                $isNormalStraight = false;
                break;
            }
        }
        
        if ($isNormalStraight) return true;
        
        // Check A-2-3-4-5 straight (values: 14, 5, 4, 3, 2)
        return $values[0] === 14 && $values[1] === 5 && $values[2] === 4 && $values[3] === 3 && $values[4] === 2;
    }

    public function compare(array $handA, array $handB): int
    {
        $rankA = $handA['rank'] instanceof \App\Enums\HandRank ? $handA['rank']->value : $handA['rank'];
        $rankB = $handB['rank'] instanceof \App\Enums\HandRank ? $handB['rank']->value : $handB['rank'];
        
        if ($rankA > $rankB) return 1;
        if ($rankA < $rankB) return -1;
        
        // Same rank, compare high cards
        for ($i = 0; $i < count($handA['highCards']); $i++) {
            if (!isset($handB['highCards'][$i])) break;
            
            if ($handA['highCards'][$i] > $handB['highCards'][$i]) return 1;
            if ($handA['highCards'][$i] < $handB['highCards'][$i]) return -1;
        }
        
        // Exact tie
        return 0;
    }

    /**
     * Accepts array of App\Models\GamePlayer and returns array of winning players
     */
    public function determineWinners(array $players): array
    {
        if (empty($players)) return [];
        if (count($players) === 1) return [$players[0]];
        
        // Evaluate all hands
        $evaluations = [];
        foreach ($players as $player) {
            // If player has folded, they cannot win
            if ($player->is_folded) continue;
            
            $evaluations[$player->id] = $this->evaluate($player->hand);
        }
        
        if (empty($evaluations)) return []; // All folded?
        
        // Sort players by hand strength descending
        usort($players, function($a, $b) use ($evaluations) {
            // Folded players drop to bottom
            if ($a->is_folded && !$b->is_folded) return 1;
            if (!$a->is_folded && $b->is_folded) return -1;
            if ($a->is_folded && $b->is_folded) return 0;
            
            // Note: return inverted result of compare() for descending sort
            return $this->compare($evaluations[$b->id], $evaluations[$a->id]);
        });
        
        // Top player is a winner
        $winners = [$players[0]];
        $topEval = $evaluations[$winners[0]->id];
        
        // Check for ties with top player
        for ($i = 1; $i < count($players); $i++) {
            if ($players[$i]->is_folded) continue;
            
            $eval = $evaluations[$players[$i]->id];
            if ($this->compare($topEval, $eval) === 0) {
                $winners[] = $players[$i];
            } else {
                break; // Because array is sorted, remaining hands are weaker
            }
        }
        
        return $winners;
    }
}
