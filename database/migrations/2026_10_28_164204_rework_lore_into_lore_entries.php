<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace subjects, documents and detail tables with a single lore_entries table.
     */
    public function up(): void
    {
        Schema::dropIfExists('character_lore_document_access');
        Schema::dropIfExists('character_beliefs');
        Schema::dropIfExists('lore_document_tag');
        Schema::dropIfExists('event_lore_subject');
        Schema::dropIfExists('people_details');
        Schema::dropIfExists('religion_details');
        Schema::dropIfExists('nature_details');
        Schema::dropIfExists('lore_subject_relationships');
        Schema::dropIfExists('lore_documents');
        Schema::dropIfExists('lore_subjects');

        Schema::create('lore_entries', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->timestamps();

            $table->index(['category', 'title']);
        });

        Schema::create('event_lore_entry', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lore_entry_id')->constrained()->cascadeOnDelete();
            $table->primary(['event_id', 'lore_entry_id']);
            $table->index(['lore_entry_id', 'event_id']);
        });
    }

    /**
     * The previous lore schema is intentionally not restored.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_lore_entry');
        Schema::dropIfExists('lore_entries');
    }
};
