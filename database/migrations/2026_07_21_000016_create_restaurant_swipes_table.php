<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each attendee's swipe on a shortlisted restaurant. A place every
        // attendee accepted is the group's match.
        Schema::create('restaurant_swipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('decision');
            $table->timestamps();

            $table->unique(['event_id', 'restaurant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_swipes');
    }
};
