<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supports, exercices et corrigés sous forme de liens (Google Drive, etc.) : aucun fichier stocké.
        // Rattachés au module (toujours, pour l'isolation par espace) et éventuellement à une séance.
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teaching_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('title');
            $table->string('url', 2048);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
