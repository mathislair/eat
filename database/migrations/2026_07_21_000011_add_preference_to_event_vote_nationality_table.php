<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A picked nationality now carries how the voter feels about it —
        // 'want' (🟢) or 'avoid' (🔴). Neutral is never stored (no row).
        Schema::table('event_vote_nationality', function (Blueprint $table) {
            $table->string('preference')->default('want')->after('nationality_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_vote_nationality', function (Blueprint $table) {
            $table->dropColumn('preference');
        });
    }
};
