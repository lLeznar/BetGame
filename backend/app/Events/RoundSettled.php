<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundSettled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public array $settleData;

    public function __construct(int $gameId, array $settleData)
    {
        $this->gameId = $gameId;
        $this->settleData = $settleData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('game.' . $this->gameId),
        ];
    }

    public function broadcastWith(): array
    {
        return $this->settleData;
    }
}
