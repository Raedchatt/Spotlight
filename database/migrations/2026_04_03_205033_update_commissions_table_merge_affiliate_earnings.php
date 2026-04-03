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
        // Update commissions table
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('revendeur_id')->nullable()->after('reservation_id')->constrained('revendeurs')->nullOnDelete();
            $table->string('status')->default('pending')->after('commission_revendeur');
        });

        // Drop affiliate_earnings table
        Schema::dropIfExists('affiliate_earnings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropForeign(['revendeur_id']);
            $table->dropColumn(['revendeur_id', 'status']);
        });

        Schema::create('affiliate_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revendeur_id')->constrained('revendeurs')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
};
