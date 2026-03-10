<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePlayer;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\JoinGameRequest;
use App\Http\Requests\AddFundsRequest;
use App\Services\GameService;
use App\Events\GameStateUpdated;
use App\Events\RoundSettled;
use Illuminate\Http\Request;

class GameController extends Controller
{
    protected GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function store(CreateGameRequest $request)
    {
        // Only banker can create games (in a real app you might check gate/policy)
        $game = $this->gameService->createGame($request->user(), $request->settings);
        
        return response()->json($game->load('banker'), 201);
    }

    public function show(Game $game)
    {
        // Load relationships needed for full state
        $game->load([
            'banker',
            'gamePlayers.user',
            'rounds' => function($query) { $query->latest()->take(1); },
            'rounds.bets',
            'rounds.sideBets'
        ]);
        
        return response()->json([
            'game' => $game,
            'current_pot' => $game->currentPot()
        ]);
    }

    public function join(JoinGameRequest $request, Game $game)
    {
        $player = $this->gameService->joinGame($game, $request->user(), $request->buy_in);
        
        broadcast(new GameStateUpdated($game))->toOthers();
        
        return response()->json($player, 200);
    }

    public function addFunds(AddFundsRequest $request, Game $game)
    {
        if ($request->user()->id !== $game->banker_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $player = GamePlayer::findOrFail($request->player_id);
        if ($player->game_id !== $game->id) {
            return response()->json(['message' => 'Player not in this game'], 400);
        }

        $this->gameService->addFunds($player, $request->amount);
        
        broadcast(new GameStateUpdated($game))->toOthers();

        return response()->json(['message' => 'Funds added']);
    }

    public function startRound(Request $request, Game $game)
    {
        if ($request->user()->id !== $game->banker_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $round = $this->gameService->startRound($game);
            
            // Re-load game state
            $game->refresh();
            
            // Broadcast state update
            broadcast(new GameStateUpdated($game));
            
            // We also need to broadcast CardsDealt to individual players
            // This happens in the event layer or controller. Let's do it here.
            foreach ($game->gamePlayers()->where('is_active', true)->get() as $player) {
                broadcast(new \App\Events\CardsDealt($player->id, $player->hand));
            }

            return response()->json($round, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function settleRound(Request $request, Game $game, $roundId)
    {
        if ($request->user()->id !== $game->banker_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $round = $game->rounds()->findOrFail($roundId);
        
        if ($round->phase->value === 'settled') {
            return response()->json(['message' => 'Round already settled'], 400);
        }

        $result = $this->gameService->settleRound($round);
        
        $game->refresh();
        
        broadcast(new GameStateUpdated($game));
        broadcast(new RoundSettled($game->id, $result));

        return response()->json($result);
    }
}
