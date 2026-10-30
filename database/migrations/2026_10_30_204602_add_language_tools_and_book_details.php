<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->json('phonemes')->nullable();
            $table->text('phonology_notes')->nullable();
            $table->json('syllables')->nullable();
            $table->text('grammar')->nullable();
            $table->text('writing_system')->nullable();
        });

        Schema::create('language_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('word');
            $table->string('meaning')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['language_id', 'word']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->json('languages')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('lore_entries')->nullOnDelete();
            $table->unsignedInteger('published_day')->nullable();
            $table->unsignedInteger('published_month')->nullable();
            $table->unsignedInteger('published_cycle')->nullable();
            $table->unsignedInteger('published_year')->nullable();
            $table->boolean('published_is_circa')->default(false);
            $table->boolean('published_is_unknown')->default(false);
        });

        DB::table('books')->orderBy('id')->each(function (object $book): void {
            $name = $book->language_id ? DB::table('languages')->where('id', $book->language_id)->value('name') : null;
            $update = ['languages' => json_encode($name ? [$name] : ['Common'])];

            if (filled($book->author)) {
                $author = DB::table('lore_entries')->where('category', 'people')->whereRaw('lower(title) = ?', [mb_strtolower(trim($book->author))])->value('id')
                    ?? DB::table('lore_entries')->insertGetId([
                        'category' => 'people',
                        'title' => trim($book->author),
                        'description' => "author of [[{$book->title}]]",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                $update['author_id'] = $author;
            }

            DB::table('books')->where('id', $book->id)->update($update);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_id');
            $table->dropColumn('author');
            $table->renameColumn('description', 'summary');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('summary', 'description');
            $table->string('author')->nullable();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_id');
            $table->dropColumn([
                'languages', 'published_day', 'published_month', 'published_cycle',
                'published_year', 'published_is_circa', 'published_is_unknown',
            ]);
        });

        Schema::dropIfExists('language_words');

        Schema::table('languages', function (Blueprint $table) {
            $table->dropColumn(['phonemes', 'phonology_notes', 'syllables', 'grammar', 'writing_system']);
        });
    }
};
