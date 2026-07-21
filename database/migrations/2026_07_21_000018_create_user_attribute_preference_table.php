<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's standing taste for a criterion, e.g. (price, €€) → 'want'.
        // The persistent counterpart to event_vote_attribute.
        Schema::create('user_attribute_preference', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');
            $table->string('preference')->default('want');

            $table->unique(['user_id', 'type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_attribute_preference');
    }
};
