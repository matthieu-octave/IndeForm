<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Durée réellement constatée lors de la dernière animation (écart prévu / réel).
        Schema::table('session_steps', function (Blueprint $table) {
            $table->unsignedInteger('actual_minutes')->nullable()->after('minutes');
        });
    }

    public function down(): void
    {
        Schema::table('session_steps', function (Blueprint $table) {
            $table->dropColumn('actual_minutes');
        });
    }
};
