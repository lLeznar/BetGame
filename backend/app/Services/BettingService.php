<?php

namespace App\Services;

use App\Models\GamePlayer;
use App\Models\Round;
use App\Models\Bet;
use App\Models\Transaction;
use App\Enums\BetType;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Exception;

class BettingService
{
    /**
     * Deducts ante from every active player
     */
    public function collectAntes(Round $round): void
    {
        $anteAmount = $round->game->settings['ante_amount'] ?? 5;
        $activePlayers = $round->game->gamePlayers()->where('is_active', true)->get();
        
        foreach ($activePlayers as $player) {
            DB::transaction(function () use ($player, $round, $anteAmount) {
                // Fresh-load from DB to avoid stale model overwriting side-bet payouts
                // that may have been applied to wallet_balance between collection and this point
                $player->refresh();
                
                $limit = $player->game->settings['max_debt_limit'] ?? 0;
                
                // If taking ante strictly violates max debt limit, they technically cannot pay the ante
                // The requirements say: "Controlled by a strict max_debt_limit".
                if ($player->wallet_balance - $anteAmount < $limit) {
                    // Normally they'd be forced to fold/sit out. We'll mark active=false.
                    $player->update(['is_active' => false]);
                    return;
                }
                
                $player->decrement('wallet_balance', $anteAmount);
                
                Bet::create([
                    'round_id' => $round->id,
                    'game_player_id' => $player->id,
                    'amount' => $anteAmount,
                    'bet_type' => BetType::Ante
                ]);
                
                Transaction::create([
                    'game_id' => $round->game_id,
                    'from_player_id' => $player->id,
                    'amount' => $anteAmount,
                    'type' => TransactionType::Ante,
                    'memo' => "Ante collected"
                ]);
            });
        }
    }

    /**
     * Calculate how much a player needs to put in to "Call" the current bet
     */
    public function calculateCallAmount(GamePlayer $player, Round $round): float
    {
        // Total amount bet *by any single player* this round
        // E.g., Player A bet 15, Player B bet 5. Max bet is 15.
        // If I am Player B, my delta is 15 - 5 = 10.
        // Note: Project.md says "system automatically calculates the delta between the table's highest bet and the player's current contribution"
        
        // We only care about the MAXIMUM total contribution by any single player.
        $tableHighestContribution = (float) (DB::table('bets')
            ->selectRaw('SUM(amount) as total_contribution')
            ->where('round_id', $round->id)
            ->where('bet_type', '!=', BetType::Ante->value)
            ->groupBy('game_player_id')
            ->orderByDesc('total_contribution')
            ->value('total_contribution') ?? 0);
            
        $myContribution = (float) Bet::where('round_id', $round->id)
            ->where('game_player_id', $player->id)
            ->where('bet_type', '!=', BetType::Ante->value)
            ->sum('amount');
            
        return max(0, $tableHighestContribution - $myContribution);
    }
    
    /**
     * Calculate call amount including Antes.
     */
    public function calculateCallAmountWithAntes(GamePlayer $player, Round $round): float
    {
        $tableHighestContribution = (float) (DB::table('bets')
            ->selectRaw('SUM(amount) as total_contribution')
            ->where('round_id', $round->id)
            ->groupBy('game_player_id')
            ->orderByDesc('total_contribution')
            ->value('total_contribution') ?? 0);
            
        $myContribution = (float) Bet::where('round_id', $round->id)
            ->where('game_player_id', $player->id)
            ->sum('amount');
            
        return max(0, $tableHighestContribution - $myContribution);
    }

    /**
     * Mark player as folded
     */
    public function fold(GamePlayer $player): void
    {
        $player->update(['is_folded' => true]);
        
        // Broadcast Event triggered in Controller
    }

    /**
     * Place a bet (Raise, Call, etc)
     */
    public function placeBet(GamePlayer $player, BetType $type, float $amount = 0): Bet
    {
        if ($player->is_folded || !$player->is_active) {
            throw new Exception("Player cannot bet.");
        }
        
        if ($amount < 0) {
            throw new Exception("Bet cannot be negative.");
        }

        return DB::transaction(function () use ($player, $type, $amount) {
            // Validate max_debt_limit
            $limit = $player->game->settings['max_debt_limit'] ?? 0;
            if (($player->wallet_balance - $amount) < $limit) {
                throw new Exception("Bet exceeds max debt limit of \${$limit}.");
            }
            
            $player->decrement('wallet_balance', $amount);
            
            // Get active round
            $round = $player->game->rounds()->latest()->first();
            if (!$round) {
                throw new Exception("No active round.");
            }
            
            $bet = Bet::create([
                'round_id' => $round->id,
                'game_player_id' => $player->id,
                'amount' => $amount,
                'bet_type' => $type
            ]);
            
            if ($amount > 0) {
                Transaction::create([
                    'game_id' => $player->game_id,
                    'from_player_id' => $player->id,
                    'amount' => $amount,
                    'type' => TransactionType::Bet,
                    'memo' => "{$type->value} placed"
                ]);
            }
            
            return $bet;
        });
    }
}
