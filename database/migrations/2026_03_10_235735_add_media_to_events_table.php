<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->string('poster_url')->nullable()->after('description');
            $table->string('poster_public_id')->nullable()->after('poster_url');
            $table->string('video_url')->nullable()->after('poster_public_id');
            $table->string('video_public_id')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'poster_url',
                'poster_public_id',
                'video_url',
                'video_public_id',
            ]);
        });
    }
};