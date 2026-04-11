<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournois', function (Blueprint $table) {
            $table->unsignedInteger('nombre_equipes')->nullable()->after('type_tournoi');
            $table->unsignedInteger('joueurs_par_equipe')->nullable()->after('nombre_equipes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournois', function (Blueprint $table) {
            $table->dropColumn(['nombre_equipes', 'joueurs_par_equipe']);
        });
    }
};
