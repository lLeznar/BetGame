<?php

namespace App\Services;

class DeckService
{
    /**
     * Generate a shuffled deck of 52 cards
     */
    public function generateDeck(): array
    {
        $suits = ['spades', 'hearts', 'diamonds', 'clubs'];
        $ranks = [
            '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10,
            'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14
        ];
        
        $deck = [];
        
        foreach ($suits as $suit) {
            foreach ($ranks as $rank => $value) {
                $deck[] = [
                    'suit' => $suit,
                    'rank' => (string) $rank,
                    'value' => $value
                ];
            }
        }
        
        shuffle($deck);
        return $deck;
    }

    /**
     * Deal 5 cards from a new deck for the given amount of players
     */
    public function dealHands(int $playerCount): array
    {
        $deck = $this->generateDeck();
        $hands = [];
        
        for ($i = 0; $i < $playerCount; $i++) {
            $hand = [];
            for ($c = 0; $c < 5; $c++) {
                $hand[] = array_pop($deck);
            }
            $hands[] = $hand;
        }
        
        return [
            'hands' => $hands,
            'remaining' => $deck
        ];
    }
}
