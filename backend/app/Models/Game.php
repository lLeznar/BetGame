<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'phase' => \App\Enums\GamePhase::class,
        ];
    }

    public function banker()
    {
        return $this->belongsTo(User::class, 'banker_id');
    }

    public function gamePlayers()
    {
        return $this->hasMany(GamePlayer::class);
    }

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentPot()
    {
        $activeRound = $this->rounds()->latest()->first();
        if (!$activeRound) return $this->rollover_pot;
        
        return $this->rollover_pot + $activeRound->bets()->sum('amount');
    }
}
