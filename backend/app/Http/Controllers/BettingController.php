<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Round;
use App\Models\GamePlayer;
use App\Http\Requests\PlaceBetRequest;
use App\Services\BettingService;
use App\Events\BetPlaced;
use App\Events\PlayerFolded;
use App\Events\GameStateUpdated;
use Illuminate\Http\Request;

class BettingController extends Controller
{
    protected BettingService $bettingService;

    public function __construct(BettingService $bettingService)
    {
        $this->bettingService = $bettingService;
    }

    private function getPlayer(Request $request, Round $round)
    {
        return GamePlayer::where('game_id', $round->game_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    public function bet(PlaceBetRequest $request, Round $round)
    {
        try {
            $player = $this->getPlayer($request, $round);
            $betType = \App\Enums\BetType::from($request->type);
            
            $bet = $this->bettingService->placeBet($player, $betType, $request->amount ?? 0);
            
            // Reload game for pot total
            $game = $round->game;
            $game->refresh();
            
            broadcast(new BetPlaced(
                $game->id, 
                $player->user->name, 
                $betType, 
                $request->amount ?? 0, 
                $game->currentPot()
            ));
            
            broadcast(new GameStateUpdated($game));
            
            return response()->json($bet, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function call(Request $request, Round $round)
    {
        try {
            $player = $this->getPlayer($request, $round);
            
            // We need to calculate what they owe.
            $callAmount = $this->bettingService->calculateCallAmount($player, $round);
            
            $bet = $this->bettingService->placeBet($player, \App\Enums\BetType::Call, $callAmount);
            
            $game = $round->game;
            $game->refresh();
            
            broadcast(new BetPlaced(
                $game->id, 
                $player->user->name, 
                \App\Enums\BetType::Call, 
                $callAmount, 
                $game->currentPot()
            ));
            
            broadcast(new GameStateUpdated($game));
            
            return response()->json($bet, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function check(Request $request, Round $round)
    {
        try {
            $player = $this->getPlayer($request, $round);

            // Verify no one has raised — call amount must be 0 to allow a check
            $callAmount = $this->bettingService->calculateCallAmount($player, $round);
            if ($callAmount > 0) {
                return response()->json(['message' => 'Cannot check — there is an active raise. You must call or fold.'], 400);
            }

            $bet = $this->bettingService->placeBet($player, \App\Enums\BetType::Check, 0);

            $game = $round->game;
            broadcast(new BetPlaced($game->id, $player->user->name, \App\Enums\BetType::Check, 0, $game->currentPot()));
            broadcast(new GameStateUpdated($game));

            return response()->json($bet, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function fold(Request $request, Round $round)
    {
        try {
            $player = $this->getPlayer($request, $round);
            
            $this->bettingService->fold($player);
            
            $game = $round->game;
            broadcast(new PlayerFolded($game->id, $player->user->name));
            broadcast(new GameStateUpdated($game));
            
            return response()->json(['message' => 'Folded successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function callAmount(Request $request, Round $round)
    {
        $player = $this->getPlayer($request, $round);
        $amount = $this->bettingService->calculateCallAmount($player, $round);
        
        return response()->json(['amount' => $amount]);
    }
}
