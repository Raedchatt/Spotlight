<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the reservations table with all required fields:
     * - user_id: the spectator making the reservation
     * - evenement_id: the targeted event
     * - statut: lifecycle state (pending | confirmed | cancelled)
     * - note: optional message from the user
     * A unique constraint on (user_id, evenement_id) prevents duplicate reservations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // The user making the reservation
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // The event being reserved for
            $table->foreignId('evenement_id')
                  ->constrained('evenements')
                  ->cascadeOnDelete();

            // Reservation status: pending | confirmed | cancelled
            $table->string('statut')->default('pending');

            // Optional note from the user (e.g. special requirements)
            $table->text('note')->nullable();

            $table->timestamps();

            // One user cannot reserve the same event twice
            $table->unique(['user_id', 'evenement_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
