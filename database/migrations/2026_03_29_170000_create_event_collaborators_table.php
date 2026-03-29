<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->onDelete('cascade');
            $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
            $table->enum('statut', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['evenement_id', 'organizer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_collaborators');
    }
};
