<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same three-way preference for a picked criterion (price/diet/style):
        // 'want' (🟢) or 'avoid' (🔴). Neutral is the absence of a row.
        Schema::table('event_vote_attribute', function (Blueprint $table) {
            $table->string('preference')->default('want')->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('event_vote_attribute', function (Blueprint $table) {
            $table->dropColumn('preference');
        });
    }
};
