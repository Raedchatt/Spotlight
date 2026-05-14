<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds stripe_transfer_id to commissions table to store proof of real Stripe payouts.
     */
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->string('stripe_transfer_id')->nullable()->after('status')
                  ->comment('Stripe Transfer ID once the reseller has been paid out via Stripe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn('stripe_transfer_id');
        });
    }
};
