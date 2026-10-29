<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('lore_entries')->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('level')->default(1);
            $table->timestamps();

            $table->unique(['organisation_id', 'name']);
            $table->index(['organisation_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_ranks');
    }
};
