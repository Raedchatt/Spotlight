<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add 'autre' to the categorie enum
        DB::statement("ALTER TABLE evenements MODIFY COLUMN categorie ENUM('sportifs','culturels','scientifiques','musicaux','commerciaux','autre')");

        // Add a column for the custom category name when 'autre' is selected
        Schema::table('evenements', function (Blueprint $table) {
            $table->string('categorie_autre')->nullable()->after('categorie');
        });
    }

    public function down(): void
    {
        // Remove the custom category column
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn('categorie_autre');
        });

        // Revert enum
        DB::statement("ALTER TABLE evenements MODIFY COLUMN categorie ENUM('sportifs','culturels','scientifiques','musicaux','commerciaux')");
    }
};
