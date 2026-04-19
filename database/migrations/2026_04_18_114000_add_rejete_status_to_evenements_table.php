<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'rejete' to the statut enum
        DB::statement("ALTER TABLE evenements MODIFY COLUMN statut ENUM('ouvert','ferme','encours','en_attente','annule','valide','rejete') DEFAULT 'ouvert'");
    }

    public function down(): void
    {
        // Revert enum
        DB::statement("ALTER TABLE evenements MODIFY COLUMN statut ENUM('ouvert','ferme','encours','en_attente','annule','valide') DEFAULT 'ouvert'");
    }
};
