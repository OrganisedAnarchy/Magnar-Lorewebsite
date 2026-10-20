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
        Schema::create('people_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lore_subject_id')
                ->unique()
                ->constrained('lore_subjects')
                ->cascadeOnDelete();
            $table->unsignedInteger('birth_world_year');
            $table->unsignedTinyInteger('birth_cycle');
            $table->unsignedTinyInteger('birth_month');
            $table->unsignedTinyInteger('birth_day');
            $table->boolean('birth_date_is_approximate')->default(false);
            $table->unsignedInteger('death_world_year')->nullable();
            $table->unsignedTinyInteger('death_cycle')->nullable();
            $table->unsignedTinyInteger('death_month')->nullable();
            $table->unsignedTinyInteger('death_day')->nullable();
            $table->boolean('death_date_is_approximate')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people_details');
    }
};
