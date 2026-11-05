<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('is_celestial_body')->default(false)->after('description');
            $table->string('location_type')->nullable()->after('celestial_kind');
            $table->string('building_type')->nullable()->after('location_type');
            $table->string('settlement_type')->nullable()->after('building_type');
        });

        DB::table('locations')->whereNotNull('celestial_slug')->update(['is_celestial_body' => true]);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['is_celestial_body', 'location_type', 'building_type', 'settlement_type']);
        });
    }
};
