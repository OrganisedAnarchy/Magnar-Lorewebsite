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
        Schema::create('lore_subject_relationships', function (Blueprint $table) {
            $table->foreignId('lore_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_lore_subject_id')->constrained('lore_subjects')->cascadeOnDelete();
            $table->string('relationship');
            $table->primary(['lore_subject_id', 'related_lore_subject_id', 'relationship']);

            $table->index(
                ['related_lore_subject_id', 'lore_subject_id'],
                'lore_subject_relationships_related_subject_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lore_subject_relationships');
    }
};
