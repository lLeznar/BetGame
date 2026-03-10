<?php

namespace App\Services;

use App\Models\User;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Models\Round;
use App\Models\Transaction;
use App\Enums\GamePhase;
use App\Enums\RoundPhase;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Exception;

class GameService
{
    protected DeckService $deckService;
    protected SideBetService $sideBetService;
    protected BettingService $bettingService;
    protected HandEvaluatorService $handEvaluator;

    public function __construct(
        DeckService $deckService,
        SideBetService $sideBetService,
        BettingService $bettingService,
        HandEvaluatorService $handEvaluator
    ) {
        $this->deckService = $deckService;
        $this->sideBetService = $sideBetService;
        $this->bettingService = $bettingService;
        $this->handEvaluator = $handEvaluator;
    }

    /**
     * Create a new game
     */
    public function createGame(User $banker, array $settings): Game
    {
        return Game::create([
            'banker_id' => $banker->id,
            'phase' => GamePhase::Waiting,
            'settings' => array_merge([
                'ante_amount' => 5,
                'max_debt_limit' => 0,
                'side_bets_enabled' => false
            ], $settings),
            'rollover_pot' => 0
        ]);
    }

    /**
     * Join a game
     */
    public function joinGame(Game $game, User $user, float $buyIn): GamePlayer
    {
        if ($buyIn < 0) {
            throw new Exception("Buy-in cannot be negative.");
        }

        return DB::transaction(function () use ($game, $user, $buyIn) {
            $tableSeat = $game->gamePlayers()->count() + 1;

            $player = GamePlayer::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'wallet_balance' => $buyIn,
                'seat_order' => $tableSeat,
                'is_active' => true
            ]);

            if ($buyIn > 0) {
                Transaction::create([
                    'game_id' => $game->id,
                    'to_player_id' => $player->id,
                    'amount' => $buyIn,
                    'type' => TransactionType::BuyIn,
                    'memo' => "Initial Buy-in"
                ]);
            }

            return $player;
        });
    }

    /**
     * Start a new round
     */
    public function startRound(Game $game): Round
    {
        return DB::transaction(function () use ($game) {
            $game->update(['phase' => GamePhase::Dealing]);

            // Create Round
            $roundNumber = $game->rounds()->count() + 1;
            $round = Round::create([
                'game_id' => $game->id,
                'round_number' => $roundNumber,
                'phase' => RoundPhase::Betting
            ]);

            $activePlayers = $game->gamePlayers()->where('is_active', true)->orderBy('id')->get();
            if ($activePlayers->count() < 2) {
                throw new Exception("Not enough active players to start a round.");
            }

            // Deal Cards
            $dealResult = $this->deckService->dealHands($activePlayers->count());
            $hands = $dealResult['hands'];

            foreach ($activePlayers as $index => $player) {
                // Reset fold status for new round
                $player->update([
                    'hand' => $hands[$index],
                    'is_folded' => false
                ]);

                // Evaluate Side Bets instantly
                $this->sideBetService->evaluateAll($player, $round);
            }

            // Collect Antes
            $this->bettingService->collectAntes($round);

            $game->update(['phase' => GamePhase::Betting]);

            return $round;
        });
    }

    /**
     * Banker adds funds to a player (physical cash to digital balance)
     */
    public function addFunds(GamePlayer $player, float $amount): void
    {
        if ($amount <= 0) {
            throw new Exception("Amount must be positive.");
        }

        DB::transaction(function () use ($player, $amount) {
            $player->increment('wallet_balance', $amount);

            Transaction::create([
                'game_id' => $player->game_id,
                'to_player_id' => $player->id,
                'amount' => $amount,
                'type' => TransactionType::BuyIn,
                'memo' => "Added Funds"
            ]);
        });
    }

    /**
     * Settle the round (Showdown)
     */
    public function settleRound(Round $round): array
    {
        return DB::transaction(function () use ($round) {
            $game = $round->game;
            $game->update(['phase' => GamePhase::Showdown]);
            $round->update(['phase' => RoundPhase::Showdown]);

            $potTotal = $game->rollover_pot + $round->bets()->sum('amount');
            $activeNonFoldedPlayers = $game->gamePlayers()
                ->where('is_active', true)
                ->where('is_folded', false)
                ->get()
                ->all();

            $winners = [];
            $payouts = [];

            if (count($activeNonFoldedPlayers) === 0) {
                // Everyone folded? Odd edge case, pot rolls over
                $game->update(['rollover_pot' => $potTotal]);
            } else if (count($activeNonFoldedPlayers) === 1) {
                // Only one person didn't fold. They win the pot automatically.
                $winners = $activeNonFoldedPlayers;
                $winner = $winners[0];
                $winner->increment('wallet_balance', $potTotal);
                
                $payouts[$winner->id] = $potTotal;

                if ($potTotal > 0) {
                    Transaction::create([
                        'game_id' => $game->id,
                        'to_player_id' => $winner->id,
                        'amount' => $potTotal,
                        'type' => TransactionType::Payout,
                        'memo' => "Won by default (others folded)"
                    ]);
                }
                
                $game->update(['rollover_pot' => 0]);
            } else {
                // Normal showdown
                $winners = $this->handEvaluator->determineWinners($activeNonFoldedPlayers);
                
                if (count($winners) > 0) {
                    // Split pot if tie
                    $splitAmount = floor($potTotal / count($winners));
                    $remainder = $potTotal - ($splitAmount * count($winners));
                    
                    foreach ($winners as $index => $winner) {
                        $payout = $splitAmount;
                        // Give remainder to first winner for simplicity
                        if ($index === 0) $payout += $remainder;
                        
                        $winner->increment('wallet_balance', $payout);
                        
                        $payouts[$winner->id] = $payout;

                        if ($payout > 0) {
                            $handEvaluation = $this->handEvaluator->evaluate($winner->hand);
                            Transaction::create([
                                'game_id' => $game->id,
                                'to_player_id' => $winner->id,
                                'amount' => $payout,
                                'type' => TransactionType::Payout,
                                'memo' => "Won Showdown: " . ($handEvaluation['label'] ?? 'Unknown')
                            ]);
                        }
                    }
                    $game->update(['rollover_pot' => 0]);
                } else {
                    $game->update(['rollover_pot' => $potTotal]);
                }
            }

            $round->update(['phase' => RoundPhase::Settled]);
            $game->update(['phase' => GamePhase::Waiting]);

            return [
                'winners' => $winners,
                'payouts' => $payouts,
                'pot_total' => $potTotal
            ];
        });
    }
}
