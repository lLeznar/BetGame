<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\HandEvaluatorService;
use App\Enums\HandRank;

class HandEvaluatorTest extends TestCase
{
    protected HandEvaluatorService $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = app(HandEvaluatorService::class);
    }

    public function test_evaluates_royal_flush()
    {
        $hand = [
            ['suit' => 'spades', 'rank' => 'A', 'value' => 14],
            ['suit' => 'spades', 'rank' => 'K', 'value' => 13],
            ['suit' => 'spades', 'rank' => 'Q', 'value' => 12],
            ['suit' => 'spades', 'rank' => 'J', 'value' => 11],
            ['suit' => 'spades', 'rank' => '10', 'value' => 10],
        ];

        $result = $this->evaluator->evaluate($hand);
        
        $this->assertEquals(HandRank::RoyalFlush, $result['rank']);
    }

    public function test_evaluates_full_house()
    {
        $hand = [
            ['suit' => 'spades', 'rank' => 'A', 'value' => 14],
            ['suit' => 'hearts', 'rank' => 'A', 'value' => 14],
            ['suit' => 'diamonds', 'rank' => 'A', 'value' => 14],
            ['suit' => 'spades', 'rank' => 'K', 'value' => 13],
            ['suit' => 'hearts', 'rank' => 'K', 'value' => 13],
        ];

        $result = $this->evaluator->evaluate($hand);
        
        $this->assertEquals(HandRank::FullHouse, $result['rank']);
    }

    public function test_compare_hands()
    {
        $flush = [
            ['suit' => 'spades', 'rank' => 'A', 'value' => 14],
            ['suit' => 'spades', 'rank' => 'K', 'value' => 13],
            ['suit' => 'spades', 'rank' => '2', 'value' => 2],
            ['suit' => 'spades', 'rank' => '4', 'value' => 4],
            ['suit' => 'spades', 'rank' => '8', 'value' => 8],
        ];

        $threeOfAKind = [
            ['suit' => 'spades', 'rank' => '7', 'value' => 7],
            ['suit' => 'hearts', 'rank' => '7', 'value' => 7],
            ['suit' => 'diamonds', 'rank' => '7', 'value' => 7],
            ['suit' => 'spades', 'rank' => 'K', 'value' => 13],
            ['suit' => 'hearts', 'rank' => '2', 'value' => 2],
        ];

        $evalFlush = $this->evaluator->evaluate($flush);
        $evalThree = $this->evaluator->evaluate($threeOfAKind);

        $this->assertEquals(1, $this->evaluator->compare($evalFlush, $evalThree));
        $this->assertEquals(-1, $this->evaluator->compare($evalThree, $evalFlush));
    }
}
