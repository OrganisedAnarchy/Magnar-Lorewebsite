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
        Schema::create('nature_details', function (Blueprint $table) {
            $table->foreignId('lore_subject_id')
                ->primary()
                ->constrained('lore_subjects')
                ->cascadeOnDelete();
            $table->text('magical_properties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nature_details');
    }
};
