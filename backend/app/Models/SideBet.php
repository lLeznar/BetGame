<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SideBet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => \App\Enums\SideBetType::class,
            'won' => 'boolean',
            'evaluated_at' => 'datetime',
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
