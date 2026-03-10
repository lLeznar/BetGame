<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Services\GameService;
use App\Services\DeckService;

class BettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mockDeck = \Mockery::mock(\App\Services\DeckService::class);
        $mockDeck->shouldReceive('dealHands')->andReturn([
            'hands' => [
                [
                    ['suit' => 'spades', 'rank' => '2', 'value' => 2],
                    ['suit' => 'diamonds', 'rank' => '4', 'value' => 4],
                    ['suit' => 'spades', 'rank' => '6', 'value' => 6],
                    ['suit' => 'diamonds', 'rank' => '8', 'value' => 8],
                    ['suit' => 'spades', 'rank' => '10', 'value' => 10],
                ],
                [
                    ['suit' => 'spades', 'rank' => '3', 'value' => 3],
                    ['suit' => 'diamonds', 'rank' => '5', 'value' => 5],
                    ['suit' => 'spades', 'rank' => '7', 'value' => 7],
                    ['suit' => 'diamonds', 'rank' => '9', 'value' => 9],
                    ['suit' => 'spades', 'rank' => 'J', 'value' => 11],
                ]
            ],
            'remaining' => []
        ]);
        $this->app->instance(\App\Services\DeckService::class, $mockDeck);
    }

    public function test_player_can_place_raise()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);
        
        $gameService = app(GameService::class);
        $game = $gameService->createGame($banker, ['ante_amount' => 5]);
        $gameService->joinGame($game, $banker, 100);
        $gameService->joinGame($game, $player1, 100);
        
        $round = $gameService->startRound($game);
        
        $response = $this->actingAs($player1)->postJson("/api/rounds/{$round->id}/bet", [
            'type' => 'raise',
            'amount' => 10
        ]);
        
        $response->assertStatus(200);
        
        $gp = GamePlayer::where('user_id', $player1->id)->first();
        $this->assertEquals(85, $gp->wallet_balance); // 100 - 5 (ante) - 10 (raise)
    }

    public function test_player_can_fold()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);
        
        $gameService = app(GameService::class);
        $game = $gameService->createGame($banker, ['ante_amount' => 5]);
        $gameService->joinGame($game, $banker, 100);
        $gameService->joinGame($game, $player1, 100);
        
        $round = $gameService->startRound($game);
        
        $this->actingAs($player1)->postJson("/api/rounds/{$round->id}/fold")->assertStatus(200);
        
        $gp = GamePlayer::where('user_id', $player1->id)->first();
        $this->assertTrue($gp->is_folded);
    }

    public function test_debt_limit_prevents_betting()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);
        
        $gameService = app(GameService::class);
        $game = $gameService->createGame($banker, ['ante_amount' => 5, 'max_debt_limit' => -50]);
        $gameService->joinGame($game, $banker, 100);
        $gameService->joinGame($game, $player1, 10); // Buy in 10
        
        $round = $gameService->startRound($game); // Ante 5 leaves 5
        
        // Max negative is -50. Balance is 5.
        // If they raise 60, balance becomes -55, which is < -50
        $response = $this->actingAs($player1)->postJson("/api/rounds/{$round->id}/bet", [
            'type' => 'raise',
            'amount' => 60
        ]);
        
        $response->assertStatus(400); // Expecting error
        
        $gp = GamePlayer::where('user_id', $player1->id)->first();
        $this->assertEquals(5, $gp->fresh()->wallet_balance); // Balance unmodified
    }
}
