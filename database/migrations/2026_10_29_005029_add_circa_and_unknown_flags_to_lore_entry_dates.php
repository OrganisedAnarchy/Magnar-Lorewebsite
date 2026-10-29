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
        Schema::table('lore_entries', function (Blueprint $table) {
            foreach (['dob', 'dod'] as $date) {
                $table->boolean("{$date}_is_circa")->default(false);
                $table->boolean("{$date}_is_unknown")->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lore_entries', function (Blueprint $table) {
            $table->dropColumn([
                'dob_is_circa',
                'dob_is_unknown',
                'dod_is_circa',
                'dod_is_unknown',
            ]);
        });
    }
};
