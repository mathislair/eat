<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-many: a nationality can live in several groups
        // (Thai is both "Asian" and "Spicy") and a group holds many nationalities.
        Schema::create('group_nationality', function (Blueprint $table) {
            $table->foreignId('nationality_group_id')->constrained('nationality_groups')->cascadeOnDelete();
            $table->foreignId('nationality_id')->constrained('nationalities')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['nationality_group_id', 'nationality_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_nationality');
    }
};
