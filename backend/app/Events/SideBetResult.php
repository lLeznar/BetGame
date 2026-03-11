<?php

namespace App\Events;

use App\Enums\SideBetType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SideBetResult implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public string $playerName;
    public SideBetType $sideBetType;
    public bool $won;
    public float $payout;

    public function __construct(int $gameId, string $playerName, SideBetType $sideBetType, bool $won, float $payout)
    {
        $this->gameId = $gameId;
        $this->playerName = $playerName;
        $this->sideBetType = $sideBetType;
        $this->won = $won;
        $this->payout = $payout;
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
            'player_name' => $this->playerName,
            'side_bet_type' => $this->sideBetType->value,
            'won' => $this->won,
            'payout' => $this->payout
        ];
    }
}
