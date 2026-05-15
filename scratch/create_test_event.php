<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Evenement;

$e = Evenement::create([
    'organisateur_id' => 34,
    'titre' => 'Team Test Event',
    'description' => 'Test tournament with 10 teams of 8 players',
    'date_debut' => '2026-06-01 10:00:00',
    'date_fin' => '2026-06-02 18:00:00',
    'lieu' => 'Test Arena',
    'prix_spectateur' => 10,
    'capacite_spectateur' => 50,
    'categorie' => 'Gaming',
    'is_tournoi' => true,
    'type_tournoi' => 'equipe',
    'prix_participant' => 20,
    'capacite_participant' => 80,
    'statut' => 'ouvert'
]);

$e->tournoi()->create([
    'prix_participant' => 20,
    'capacite_participant' => 80,
    'type_tournoi' => 'equipe',
    'nombre_equipes' => 10,
    'joueurs_par_equipe' => 8
]);

echo "CREATED_ID: " . $e->id . "\n";
