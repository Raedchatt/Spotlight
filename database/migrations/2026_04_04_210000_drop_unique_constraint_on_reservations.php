<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unique constraint on (user_id, evenement_id) so that
     * a participant can make multiple reservations for the same event.
     *
     * MySQL reuses a unique index to back a foreign key, so we must
     * drop the FK constraints first, remove the unique index, then
     * restore the foreign keys.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // 1. Drop the foreign keys that rely on the unique index columns
            $table->dropForeign(['user_id']);
            $table->dropForeign(['evenement_id']);

            // 2. Now we can safely drop the unique index
            if (Schema::hasIndex('reservations', 'reservations_user_id_evenement_id_unique')) {
                $table->dropUnique('reservations_user_id_evenement_id_unique');
            }

            // 3. Re-create the individual column indexes that MySQL needs
            //    to back the foreign keys (if they don't already exist)
            $table->index('user_id');
            $table->index('evenement_id');

            // 4. Restore the foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('evenement_id')->references('id')->on('evenements')->cascadeOnDelete();
        });
    }

    /**
     * Restore the unique constraint on rollback.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Drop the FKs and plain indexes first
            $table->dropForeign(['user_id']);
            $table->dropForeign(['evenement_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['evenement_id']);

            // Re-create the unique index (covers both columns)
            $table->unique(['user_id', 'evenement_id']);

            // Restore foreign keys (unique index now backs them)
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('evenement_id')->references('id')->on('evenements')->cascadeOnDelete();
        });
    }
};
