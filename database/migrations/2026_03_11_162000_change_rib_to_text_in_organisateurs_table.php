<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change the column type to text
        Schema::table('organisateurs', function (Blueprint $table) {
            $table->text('rib')->nullable()->change();
        });

        // 2. Encrypt existing plain-text RIBs
        $organisateurs = DB::table('organisateurs')->whereNotNull('rib')->get();
        foreach ($organisateurs as $organisateur) {
            // Check if it's already encrypted (very basic check: Laravel encryption starts with specific characters usually, but since we just changed it, we assume they are plain text)
            // To be safe, we try to decrypt. If it fails, it's definitely plain text.
            try {
                Crypt::decryptString($organisateur->rib);
            } catch (\Exception $e) {
                // Not encrypted, let's encrypt it
                DB::table('organisateurs')
                    ->where('id', $organisateur->id)
                    ->update(['rib' => Crypt::encryptString($organisateur->rib)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Decrypting on rollback is tricky because the model cast might be active or not
        // However, for completeness:
        $organisateurs = DB::table('organisateurs')->whereNotNull('rib')->get();
        foreach ($organisateurs as $organisateur) {
            try {
                $decrypted = Crypt::decryptString($organisateur->rib);
                DB::table('organisateurs')
                    ->where('id', $organisateur->id)
                    ->update(['rib' => $decrypted]);
            } catch (\Exception $e) {
                // Already plain text or different key
            }
        }

        Schema::table('organisateurs', function (Blueprint $table) {
            $table->string('rib', 255)->nullable()->change();
        });
    }
};
