<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add nombre_tickets to reservations.
     * Required to calculate the total amount (prix × nombre_tickets).
     * Defaults to 1 so existing rows stay valid.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedSmallInteger('nombre_tickets')->default(1)->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('nombre_tickets');
        });
    }
};
