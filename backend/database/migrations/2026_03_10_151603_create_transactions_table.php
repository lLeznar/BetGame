<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_player_id')->nullable()->constrained('game_players')->nullOnDelete();
            $table->foreignId('to_player_id')->nullable()->constrained('game_players')->nullOnDelete();
            $table->decimal('amount', 12, 2); // Signed
            $table->enum('type', ['buy_in', 'payout', 'ante', 'bet', 'side_bet_payout', 'rollover']);
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
