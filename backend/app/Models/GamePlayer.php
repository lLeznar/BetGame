<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GamePlayer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'hand' => 'array',
            'is_folded' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
