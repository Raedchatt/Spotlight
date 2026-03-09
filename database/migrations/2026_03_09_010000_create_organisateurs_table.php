<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the organisateurs table.
     *
     * An organizer profile is linked 1-to-1 with a user.
     * The statut column (pending | approved | rejected) drives the approval flow.
     */
    public function up(): void
    {
        Schema::create('organisateurs', function (Blueprint $table) {
            $table->id();

            // One organizer profile per user
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('nom_organisation');
            $table->text('description')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->string('site_web')->nullable();

            // Path to the organizer's logo (stored in public disk)
            $table->string('logo')->nullable();

            // Approval status: pending | approved | rejected
            $table->string('statut')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organisateurs');
    }
};
