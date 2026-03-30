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
        // Enums in MySQL are notoriously hard to upgrade using Laravel blueprints without doctrine/dbal.
        // The safest and cleanest way to add new notification types is to convert the column to standard VARCHAR.
        // We already enforce enum strictness in the PHP application layer via App\Enums\TypeNotification.
        DB::statement('ALTER TABLE notifications MODIFY type VARCHAR(255) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We do not strictly revert to an explicit ENUM definition because string is a safe superset 
        // and doesn't truncate data dynamically.
    }
};
