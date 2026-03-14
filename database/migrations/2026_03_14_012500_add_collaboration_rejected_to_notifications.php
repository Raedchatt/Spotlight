<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->change(); // widen to allow new enum value
        });

        // Add the new enum value
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('evenement_cree','evenement_modifie','evenement_supprime','reservation_cree','reservation_annulee','invitation_collaboration','collaboration_acceptee','collaboration_rejected')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('evenement_cree','evenement_modifie','evenement_supprime','reservation_cree','reservation_annulee','invitation_collaboration','collaboration_acceptee')");
    }
};
