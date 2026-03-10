<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Round extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'phase' => \App\Enums\RoundPhase::class,
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function sideBets()
    {
        return $this->hasMany(SideBet::class);
    }
}
