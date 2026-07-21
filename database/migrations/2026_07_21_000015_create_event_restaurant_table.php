<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The ranked shortlist computed for an event once voting closes: the
        // restaurants that best fit the room, vetoed cuisines already dropped.
        Schema::create('event_restaurant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->integer('match_score');
            $table->unsignedInteger('position');

            $table->unique(['event_id', 'restaurant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_restaurant');
    }
};
