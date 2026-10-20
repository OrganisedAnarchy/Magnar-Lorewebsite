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
        Schema::create('place_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('place_place_type', function (Blueprint $table) {
            $table->foreignId('place_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['place_id', 'place_type_id']);
            $table->index(['place_type_id', 'place_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('place_place_type');
        Schema::dropIfExists('place_types');
    }
};
