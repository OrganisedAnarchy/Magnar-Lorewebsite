<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('lore_entries')->where('category', 'languages')->orderBy('id')->each(function (object $entry) use ($now): void {
            DB::table('languages')->insertOrIgnore([
                'name' => $entry->title,
                'description' => $entry->description,
                'created_at' => $entry->created_at ?? $now,
                'updated_at' => $entry->updated_at ?? $now,
            ]);
        });

        DB::table('lore_entries')->where('category', 'languages')->delete();
    }

    public function down(): void
    {
        DB::table('languages')->orderBy('id')->each(function (object $language): void {
            DB::table('lore_entries')->insert([
                'category' => 'languages',
                'title' => $language->name,
                'description' => $language->description,
                'created_at' => $language->created_at,
                'updated_at' => $language->updated_at,
            ]);
        });

        Schema::dropIfExists('books');
        Schema::dropIfExists('languages');
    }
};
