<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The nationalities an attendee picked on their ballot.
        Schema::create('event_vote_nationality', function (Blueprint $table) {
            $table->foreignId('event_vote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nationality_id')->constrained()->cascadeOnDelete();

            $table->unique(['event_vote_id', 'nationality_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_vote_nationality');
    }
};
