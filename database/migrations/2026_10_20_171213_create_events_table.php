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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description');
            $table->unsignedInteger('world_year');
            $table->unsignedTinyInteger('cycle');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->foreignId('place_id')
                ->nullable()
                ->constrained('places')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['world_year', 'cycle', 'month', 'day']);
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
