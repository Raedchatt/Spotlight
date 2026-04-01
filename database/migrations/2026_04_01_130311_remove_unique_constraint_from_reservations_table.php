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
        Schema::table('reservations', function (Blueprint $table) {
            // Add explicit indexes so the foreign key constraints 
            // don't fail when the unique index is dropped.
            $table->index('user_id');
            $table->index('evenement_id');
            $table->dropUnique(['user_id', 'evenement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['user_id', 'evenement_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['evenement_id']);
        });
    }
};
