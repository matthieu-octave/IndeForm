<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
        });

        // Seul le module porte workspace_id : séances et étapes en héritent par leur parent.
        // Colonne nullable pour pouvoir migrer une base existante ; l'application la renseigne toujours
        // et les lignes sans espace restent invisibles de tous.
        Schema::table('course_modules', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
        });
        Schema::dropIfExists('workspaces');
    }
};
