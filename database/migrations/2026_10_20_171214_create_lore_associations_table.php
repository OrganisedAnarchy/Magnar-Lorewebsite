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
        Schema::create('character_tag', function (Blueprint $table) {
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['character_id', 'tag_id']);
            $table->index(['tag_id', 'character_id']);
        });

        Schema::create('lore_document_tag', function (Blueprint $table) {
            $table->foreignId('lore_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['lore_document_id', 'tag_id']);
            $table->index(['tag_id', 'lore_document_id']);
        });

        Schema::create('event_lore_subject', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lore_subject_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'lore_subject_id']);
            $table->index(['lore_subject_id', 'event_id']);
        });

        Schema::create('event_tag', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'tag_id']);
            $table->index(['tag_id', 'event_id']);
        });

        Schema::create('character_beliefs', function (Blueprint $table) {
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lore_subject_id')->constrained()->cascadeOnDelete();
            $table->enum('belief_type', ['religion', 'god']);
            $table->primary(['character_id', 'lore_subject_id', 'belief_type']);
            $table->index(['lore_subject_id', 'belief_type']);
        });

        Schema::create('character_lore_document_access', function (Blueprint $table) {
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lore_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('granted_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->primary(['character_id', 'lore_document_id']);

            $table->index('granted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_lore_document_access');
        Schema::dropIfExists('character_beliefs');
        Schema::dropIfExists('event_tag');
        Schema::dropIfExists('event_lore_subject');
        Schema::dropIfExists('lore_document_tag');
        Schema::dropIfExists('character_tag');
    }
};
