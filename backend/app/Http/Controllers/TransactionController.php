<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Game $game)
    {
        $transactions = $game->transactions()
            ->with(['fromPlayer.user', 'toPlayer.user'])
            ->latest()
            ->get();
            
        return response()->json($transactions);
    }
}
