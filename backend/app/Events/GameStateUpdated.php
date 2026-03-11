<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Game $game;

    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->game->id),
        ];
    }

    public function broadcastWith(): array
    {
        // Ensure relations are loaded for full state synchronization
        $this->game->load([
            'banker',
            'gamePlayers.user',
            'rounds' => function($query) { $query->latest()->take(1); },
            'rounds.bets',
            'rounds.sideBets'
        ]);

        return [
            'game' => $this->game->toArray(),
            'current_pot' => (float) $this->game->currentPot()
        ];
    }
}
