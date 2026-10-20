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
        Schema::create('lore_subjects', function (Blueprint $table) {
            $table->id();
            $table->enum('category', [
                'cosmic',
                'arcane',
                'location',
                'organisation',
                'nature',
                'people',
                'gods',
                'religion',
            ]);
            $table->string('name');
            $table->longText('overview');
            $table->foreignId('place_id')
                ->nullable()
                ->unique()
                ->constrained('places')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['category', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lore_subjects');
    }
};
