<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\GamePlayer;
use App\Services\GameService;
use App\Services\BettingService;
use App\Enums\TransactionType;
use App\Enums\BetType;

class TransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Core invariant: for each player, their wallet_balance must equal
     * the sum of all incoming credits minus the sum of all outgoing debits
     * as recorded in the transaction ledger.
     */
    public function test_ledger_balance_matches_wallet_for_all_players()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);
        $player2 = User::factory()->create(['is_banker' => false]);

        $gameService = app(GameService::class);
        $bettingService = app(BettingService::class);

        // Create and join
        $game = $gameService->createGame($banker, [
            'ante_amount' => 5,
            'side_bets_enabled' => true,
            'side_bet_payouts' => ['black_pair' => 10, 'one_eye' => 20, 'aces' => 30]
        ]);

        $gpBanker = $gameService->joinGame($game, $banker, 100);
        $gp1 = $gameService->joinGame($game, $player1, 50);
        $gp2 = $gameService->joinGame($game, $player2, 50);

        // Play a round (hands are random - we don't care, we just verify ledger integrity)
        $round = $gameService->startRound($game);

        // P1 raises 20, P2 calls, Banker folds
        $bettingService->placeBet($gp1, BetType::Raise, 20);
        $bettingService->placeBet($gp2, BetType::Call, 20);
        $bettingService->fold($gpBanker);

        $gameService->settleRound($round);

        // Re-read all players
        $finalGpBanker = $gpBanker->fresh();
        $finalGp1 = $gp1->fresh();
        $finalGp2 = $gp2->fresh();

        // Core Ledger Invariant: wallet_balance == sum(credits) - sum(debits) from transactions
        $this->assertLedgerIntegrity($finalGpBanker);
        $this->assertLedgerIntegrity($finalGp1);
        $this->assertLedgerIntegrity($finalGp2);

        // The ledger integrity check above is sufficient proof of correctness.
        // Exact balance depends on which side bets hit for any given hand.
    }

    /**
     * Run two rounds to verify ledger stays consistent over multiple rounds.
     */
    public function test_ledger_stays_consistent_across_multiple_rounds()
    {
        $banker = User::factory()->create(['is_banker' => true]);
        $player1 = User::factory()->create(['is_banker' => false]);

        $gameService = app(GameService::class);
        $bettingService = app(BettingService::class);

        $game = $gameService->createGame($banker, [
            'ante_amount' => 5,
            'side_bets_enabled' => false,
        ]);

        $gpBanker = $gameService->joinGame($game, $banker, 100);
        $gp1 = $gameService->joinGame($game, $player1, 100);

        // Round 1
        $round1 = $gameService->startRound($game);
        $bettingService->placeBet($gp1, BetType::Raise, 10);
        $bettingService->fold($gpBanker);
        $gameService->settleRound($round1);

        // Round 2 - Re-read players to get fresh is_folded state
        $gpBanker = $gpBanker->fresh();
        $gp1 = $gp1->fresh();

        $round2 = $gameService->startRound($game);
        // Re-read again after startRound resets is_folded
        $gpBanker = $gpBanker->fresh();
        $gp1 = $gp1->fresh();
        
        $bettingService->placeBet($gpBanker, BetType::Raise, 10);
        $bettingService->fold($gp1);
        $gameService->settleRound($round2);

        $finalGpBanker = $gpBanker->fresh();
        $finalGp1 = $gp1->fresh();
        
        // Trace: Log all wallets and all P1 transactions to find discrepancy
        \Illuminate\Support\Facades\Log::info("Final Banker wallet: {$finalGpBanker->wallet_balance}");
        \Illuminate\Support\Facades\Log::info("Final P1 wallet: {$finalGp1->wallet_balance}");
        $p1Txns2 = Transaction::where('to_player_id', $finalGp1->id)->orWhere('from_player_id', $finalGp1->id)->get();
        \Illuminate\Support\Facades\Log::info("P1 Transactions in Multi-Round: " . $p1Txns2->toJson());
        
        // Ledger integrity after 2 rounds
        $this->assertLedgerIntegrity($finalGpBanker);
        $this->assertLedgerIntegrity($finalGp1);
    }

    /**
     * Assert that wallet_balance matches the ledger sum of transactions.
     */
    private function assertLedgerIntegrity(GamePlayer $player): void
    {
        $credits = Transaction::where('to_player_id', $player->id)->sum('amount');
        $debits = Transaction::where('from_player_id', $player->id)->sum('amount');

        $this->assertEquals(
            (float) ($credits - $debits),
            (float) $player->wallet_balance,
            "Ledger mismatch for player {$player->id}: credits={$credits} debits={$debits} wallet={$player->wallet_balance}"
        );
    }
}
