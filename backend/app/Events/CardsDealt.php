<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardsDealt implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gamePlayerId;
    public array $hand;

    public function __construct(int $gamePlayerId, array $hand)
    {
        $this->gamePlayerId = $gamePlayerId;
        $this->hand = $hand;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('player.' . $this->gamePlayerId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'hand' => $this->hand
        ];
    }
}
