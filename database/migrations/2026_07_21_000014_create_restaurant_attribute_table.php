<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A restaurant's attributes (price/diet/style), mirroring the vocabulary
        // attendees vote on so the two can be matched directly.
        Schema::create('restaurant_attribute', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('value');

            $table->unique(['restaurant_id', 'type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_attribute');
    }
};
