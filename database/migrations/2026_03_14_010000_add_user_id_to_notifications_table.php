<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            $table->string('type')->change(); // widen to allow new enum value
        });

        // Add the new enum value
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('evenement_cree','evenement_modifie','evenement_supprime','reservation_cree','reservation_annulee','invitation_collaboration','collaboration_acceptee')");
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('evenement_cree','evenement_modifie','evenement_supprime','reservation_annulee','invitation_collaboration','collaboration_acceptee')");
    }
};
