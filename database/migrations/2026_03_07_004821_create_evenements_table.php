<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {

            $table->id();

            // relation with organisateur (user)
            $table->foreignId('organisateur_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('titre');
            $table->text('description');

            $table->dateTime('date_debut');
            $table->dateTime('date_fin');

            $table->string('lieu');

            $table->decimal('prix_spectateur', 10, 2)->default(0);

            $table->integer('capacite_spectateur');

            $table->enum('statut', [
                'ouvert',
                'ferme',
                'encours',
                'en_attente',
                'annule'
            ])->default('en_attente');

            $table->enum('categorie', [
                'sportifs',
                'culturels',
                'scientifiques',
                'musicaux',
                'commerciaux'
            ]);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};