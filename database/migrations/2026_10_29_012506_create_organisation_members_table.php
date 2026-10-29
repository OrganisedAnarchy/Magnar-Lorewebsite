<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisation_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisation_id')->constrained('lore_entries')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('lore_entries')->cascadeOnDelete();
            $table->foreignId('organisation_rank_id')->nullable()->constrained('organisation_ranks')->nullOnDelete();
            $table->unsignedTinyInteger('join_day')->nullable();
            $table->unsignedTinyInteger('join_month')->nullable();
            $table->unsignedTinyInteger('join_cycle')->nullable();
            $table->unsignedInteger('join_year')->nullable();
            $table->boolean('join_is_circa')->default(false);
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['organisation_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organisation_members');
    }
};
