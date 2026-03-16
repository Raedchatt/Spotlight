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
        Schema::table('paiements', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')->nullable()->unique()->after('montant');
            $table->string('stripe_session_id')->nullable()->unique()->after('stripe_payment_intent_id');
            $table->string('currency')->default('usd')->after('stripe_session_id');
            $table->string('payment_method')->nullable()->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_payment_intent_id',
                'stripe_session_id',
                'currency',
                'payment_method'
            ]);
        });
    }
};
