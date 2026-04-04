<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unique constraint on (user_id, evenement_id) so that
     * a participant can make multiple reservations for the same event.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Check if the unique index exists before dropping it
            if (Schema::hasIndex('reservations', 'reservations_user_id_evenement_id_unique')) {
                $table->dropUnique('reservations_user_id_evenement_id_unique');
            }
        });
    }

    /**
     * Restore the unique constraint on rollback.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique(['user_id', 'evenement_id']);
        });
    }
};
