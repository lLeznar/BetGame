<?php

namespace App\Events;

use App\Enums\BetType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BetPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;
    public string $playerName;
    public BetType $betType;
    public float $amount;
    public float $newPotTotal;

    public function __construct(int $gameId, string $playerName, BetType $betType, float $amount, float $newPotTotal)
    {
        $this->gameId = $gameId;
        $this->playerName = $playerName;
        $this->betType = $betType;
        $this->amount = $amount;
        $this->newPotTotal = $newPotTotal;
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
            'bet_type' => $this->betType->value,
            'amount' => $this->amount,
            'new_pot_total' => $this->newPotTotal
        ];
    }
}
