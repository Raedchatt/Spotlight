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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id(); // id
            $table->string('message'); // message
            
            $table->enum('type', [
                'evenement_cree',
                'evenement_modifie',
                'evenement_supprime',
                'reservation_annulee',
                'invitation_collaboration',
                'collaboration_acceptee'
            ]);

            $table->timestamp('date_envoi')->nullable(); // dateEnvoi
            $table->boolean('lu')->default(false); // lu
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
