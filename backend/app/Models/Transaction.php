<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\TransactionType::class,
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function fromPlayer()
    {
        return $this->belongsTo(GamePlayer::class, 'from_player_id');
    }

    public function toPlayer()
    {
        return $this->belongsTo(GamePlayer::class, 'to_player_id');
    }
}
