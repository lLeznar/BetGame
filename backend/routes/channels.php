<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\GamePlayer;

Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    return GamePlayer::where('game_id', $gameId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('player.{gamePlayerId}', function ($user, $gamePlayerId) {
    $gp = GamePlayer::find($gamePlayerId);
    return $gp && $gp->user_id === $user->id;
});
