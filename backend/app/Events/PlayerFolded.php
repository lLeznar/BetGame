<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerFolded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public string $playerName;

    public function __construct(int $gameId, string $playerName)
    {
        $this->gameId = $gameId;
        $this->playerName = $playerName;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->gameId),
        ];
    }
    
    public function broadcastWith(): array
    {
        return [
            'player_name' => $this->playerName
        ];
    }
}
