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
                $table->unsignedTinyInteger("{$date}_day")->nullable();
                $table->unsignedTinyInteger("{$date}_month")->nullable();
                $table->unsignedTinyInteger("{$date}_cycle")->nullable();
                $table->unsignedInteger("{$date}_year")->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lore_entries', function (Blueprint $table) {
            foreach (['dob', 'dod'] as $date) {
                $table->dropColumn([
                    "{$date}_day",
                    "{$date}_month",
                    "{$date}_cycle",
                    "{$date}_year",
                ]);
            }
        });
    }
};
