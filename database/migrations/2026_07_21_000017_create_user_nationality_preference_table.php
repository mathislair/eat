<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's standing taste for a cuisine — 'want' (🟢) or 'avoid' (🔴).
        // Mirrors an event ballot, but persists across events so it can
        // pre-fill votes and stand in for someone who never voted.
        Schema::create('user_nationality_preference', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nationality_id')->constrained()->cascadeOnDelete();
            $table->string('preference')->default('want');

            $table->unique(['user_id', 'nationality_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_nationality_preference');
    }
};
