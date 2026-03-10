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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banker_id')->constrained('users');
            $table->enum('phase', ['waiting', 'dealing', 'betting', 'showdown', 'finished'])->default('waiting');
            $table->json('settings');
            $table->decimal('rollover_pot', 12, 2)->default(0); // Decimal is signed by default in Postgres
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
