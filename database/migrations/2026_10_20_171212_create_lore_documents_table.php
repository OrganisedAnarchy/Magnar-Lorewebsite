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
        Schema::create('lore_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lore_subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->unsignedTinyInteger('access_tier');
            $table->timestamps();

            $table->index(['lore_subject_id', 'access_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lore_documents');
    }
};
