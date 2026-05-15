<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change categories.label to JSON
        // We first backup existing labels
        $categories = DB::table('categories')->get();
        
        Schema::table('categories', function (Blueprint $table) {
            $table->json('label_json')->nullable();
        });

        foreach ($categories as $cat) {
            DB::table('categories')->where('id', $cat->id)->update([
                'label_json' => json_encode(['en' => $cat->label, 'fr' => $cat->label, 'ar' => $cat->label])
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('label');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->json('label')->nullable();
        });

        foreach ($categories as $cat) {
            DB::table('categories')->where('id', $cat->id)->update([
                'label' => json_encode(['en' => $cat->label, 'fr' => $cat->label, 'ar' => $cat->label])
            ]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('label_json');
        });

        // 2. Add category_id to evenements
        Schema::table('evenements', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
        });

        // 3. Link existing evenements to categories based on the 'categorie' slug string
        $evenements = DB::table('evenements')->get();
        foreach ($evenements as $event) {
            $category = DB::table('categories')->where('slug', $event->categorie)->first();
            if ($category) {
                DB::table('evenements')->where('id', $event->id)->update([
                    'category_id' => $category->id
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('label');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('label')->nullable();
        });
    }
};
