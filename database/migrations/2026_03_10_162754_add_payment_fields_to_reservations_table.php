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
         Schema::table('reservations', function (Blueprint $table) {
        $table->string('payment_intent_id')->nullable();
        $table->decimal('amount', 8, 2)->default(0);
        $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['payment_intent_id', 'amount', 'status']);
        });
    }
};
