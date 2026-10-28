<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A null category means the entry is uncategorized.
     */
    public function up(): void
    {
        Schema::table('lore_entries', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lore_entries', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
        });
    }
};
