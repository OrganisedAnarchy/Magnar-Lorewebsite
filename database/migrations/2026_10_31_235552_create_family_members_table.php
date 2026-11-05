<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('lore_entries')->cascadeOnDelete();
            $table->foreignId('relative_id')->constrained('lore_entries')->cascadeOnDelete();
            $table->string('relationship', 20);
            $table->timestamps();

            $table->unique(['person_id', 'relative_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
