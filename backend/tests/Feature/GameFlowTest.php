<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\GamePlayer;

class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_game_lifecycle_success()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);
        $player2 = User::factory()->create(['is_banker' => false]);

        // Mock DeckService so we don't randomly hit BlackPair (+20), OneEye (+15), Aces (+10) 
        // which throws off the exact wallet_balance assertions.
        // We deal deterministic losing hands.
        $mockDeck = \Mockery::mock(\App\Services\DeckService::class);
        $mockDeck->shouldReceive('dealHands')->andReturn([
            'hands' => [
                [
                    ['suit' => 'hearts', 'rank' => '2', 'value' => 2],
                    ['suit' => 'diamonds', 'rank' => '3', 'value' => 3],
                    ['suit' => 'hearts', 'rank' => '5', 'value' => 5],
                    ['suit' => 'diamonds', 'rank' => '7', 'value' => 7],
                    ['suit' => 'hearts', 'rank' => '9', 'value' => 9],
                ],
                [
                    ['suit' => 'hearts', 'rank' => '4', 'value' => 4],
                    ['suit' => 'diamonds', 'rank' => '6', 'value' => 6],
                    ['suit' => 'hearts', 'rank' => '8', 'value' => 8],
                    ['suit' => 'diamonds', 'rank' => '10', 'value' => 10],
                    ['suit' => 'hearts', 'rank' => 'Q', 'value' => 12],
                ]
            ],
            'remaining' => []
        ]);
        $this->app->instance(\App\Services\DeckService::class, $mockDeck);

        // 1. Create Game
        $response = $this->actingAs($banker)->postJson('/api/games', [
            'settings' => [
                'ante_amount' => 5,
                'max_debt_limit' => -100,
                'side_bets_enabled' => true,
                'side_bet_payouts' => [
                    'black_pair' => 20,
                    'one_eye' => 15,
                    'aces' => 10,
                ],
            ]
        ]);
        $response->assertStatus(201);
        $gameId = $response->json('id');

        // 2. Join Game
        $this->actingAs($player1)->postJson("/api/games/{$gameId}/join", ['buy_in' => 100])->assertStatus(200);
        $this->actingAs($player2)->postJson("/api/games/{$gameId}/join", ['buy_in' => 50])->assertStatus(200);

        $game = Game::with('gamePlayers')->find($gameId);
        $this->assertCount(2, $game->gamePlayers);
        
        // 3. Start Round
        $this->actingAs($banker)->postJson("/api/games/{$gameId}/rounds")->assertStatus(200);

        // Verify state after start: cards dealt, antes collected
        $this->assertDatabaseHas('rounds', ['game_id' => $gameId, 'round_number' => 1, 'phase' => 'betting']);
        
        $gp1 = GamePlayer::where('user_id', $player1->id)->first();
        $gp2 = GamePlayer::where('user_id', $player2->id)->first();
        
        $this->assertCount(5, $gp1->hand);
        $this->assertCount(5, $gp2->hand);
        
        // Antes collected
        $this->assertEquals(95, $gp1->wallet_balance); // 100 - 5
        $this->assertEquals(45, $gp2->wallet_balance); // 50 - 5
        
        // 4. Place Bets
        $currentRound = $game->rounds()->latest()->first();
        $this->actingAs($player1)->postJson("/api/rounds/{$currentRound->id}/bet", [
            'type' => 'raise',
            'amount' => 20
        ])->assertStatus(200);
        
        // Player 2 calls
        \Illuminate\Support\Facades\Log::info('Bets before call:', \App\Models\Bet::all()->toArray());
        $amount = app(\App\Services\BettingService::class)->calculateCallAmount($gp2, $currentRound);
        \Illuminate\Support\Facades\Log::info("Call amount generated for player2: {$amount}");
        
        $this->actingAs($player2)->postJson("/api/rounds/{$currentRound->id}/call")->assertStatus(200);

        $this->assertEquals(75, $gp1->fresh()->wallet_balance); // 95 - 20
        $this->assertEquals(25, $gp2->fresh()->wallet_balance); // 45 - 20
        
        // 5. Settle Round
        $this->actingAs($banker)->postJson("/api/games/{$gameId}/rounds/{$currentRound->id}/settle")->assertStatus(200);
        
        $this->assertDatabaseHas('rounds', ['id' => $currentRound->id, 'phase' => 'settled']);
        $this->assertEquals(\App\Enums\GamePhase::Waiting, Game::find($gameId)->phase); // Game state goes back to waiting after settle
    }
}
