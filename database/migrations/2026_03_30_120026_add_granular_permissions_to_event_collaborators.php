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
        Schema::table('event_collaborators', function (Blueprint $table) {
            $table->boolean('can_edit')->default(false)->after('statut');
            $table->boolean('can_cancel')->default(false)->after('can_edit');
            $table->boolean('can_manage_team')->default(false)->after('can_cancel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_collaborators', function (Blueprint $table) {
            $table->dropColumn(['can_edit', 'can_cancel', 'can_manage_team']);
        });
    }
};
