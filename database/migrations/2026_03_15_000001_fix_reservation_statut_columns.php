<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure we don't lose data from the old string 'statut' column
        // We'll update the 'status' enum column with values from 'statut' where they match
        // and handle any mismatches (e.g., 'confirmed' vs 'paid').
        
        // First, let's make the 'status' enum column accept all possible values temporarily
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'confirmed') DEFAULT 'pending'");

        // Sync data from 'statut' to 'status' if 'status' is still 'pending' but 'statut' has info
        if (Schema::hasColumn('reservations', 'statut')) {
            DB::table('reservations')
                ->where('status', 'pending')
                ->whereIn('statut', ['confirmed', 'cancelled'])
                ->update([
                    'status' => DB::raw('statut')
                ]);
                
            // Map 'paid' to 'confirmed' if that's the intention (usually it is for reservations)
            DB::table('reservations')
                ->where('status', 'paid')
                ->update(['status' => 'confirmed']);

            // 2. Drop the redundant string 'statut' column
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('statut');
            });
        }

        // 3. Rename 'status' to 'statut' and finalize the enum values for the new 'statut' column
        // Check if we still have 'status' before renaming
        if (Schema::hasColumn('reservations', 'status')) {
            DB::statement("ALTER TABLE reservations CHANGE COLUMN status statut ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->renameColumn('statut', 'status');
            $table->string('statut')->default('pending')->after('evenement_id');
        });

        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending'");
        
        // Restore data from 'status' to 'statut'
        DB::table('reservations')->update([
            'statut' => DB::raw('status')
        ]);
    }
};
