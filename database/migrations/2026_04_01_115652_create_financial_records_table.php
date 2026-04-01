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
        Schema::create('financial_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
            $table->foreignId('evenement_id')->nullable()->constrained('evenements')->nullOnDelete();
            $table->enum('type', ['payment', 'refund', 'payout']);
            $table->integer('amount'); // Stored in cents
            $table->string('currency', 3)->default('eur');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('stripe_reference')->unique()->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_records');
    }
};
