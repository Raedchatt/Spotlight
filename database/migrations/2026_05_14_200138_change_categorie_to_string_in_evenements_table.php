<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Altering ENUM to VARCHAR requires raw DB statements in some cases,
        // but Laravel 11/10 handles string() safely if dbal is present.
        // To be safe against MariaDB/MySQL ENUM conversion issues:
        DB::statement('ALTER TABLE evenements MODIFY COLUMN categorie VARCHAR(255) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE evenements MODIFY COLUMN categorie ENUM('sportifs','culturels','scientifiques','musicaux','commerciaux','autre') NOT NULL");
    }
};
