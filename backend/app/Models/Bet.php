<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bet_type' => \App\Enums\BetType::class,
        ];
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function gamePlayer()
    {
        return $this->belongsTo(GamePlayer::class);
    }
}
