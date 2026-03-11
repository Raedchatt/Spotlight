<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            if (!Schema::hasColumn('evenements', 'is_tournoi')) {
            $table->boolean('is_tournoi')->default(false);
            $table->string('type_tournoi')->nullable(); // equipe or individuel
            $table->decimal('prix_participant', 8, 2)->nullable();
            $table->integer('capacite_participant')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['is_tournoi', 'type_tournoi', 'prix_participant', 'capacite_participant']);
        });
    }
};
