<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The criteria an attendee picked, e.g. (price, €€), (diet, vegan).
        Schema::create('event_vote_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_vote_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');

            $table->unique(['event_vote_id', 'type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_vote_attribute');
    }
};
