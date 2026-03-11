<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\GamePlayer;

Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    // Allow any authenticated user to spectate or play
    return true; 
});

Broadcast::channel('player.{gamePlayerId}', function ($user, $gamePlayerId) {
    $gp = GamePlayer::find($gamePlayerId);
    return $gp && $gp->user_id === $user->id;
});
