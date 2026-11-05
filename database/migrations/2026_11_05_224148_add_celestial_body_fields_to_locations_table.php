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
            $table->string('celestial_slug')->nullable()->unique()->after('description');
            $table->string('celestial_kind')->nullable()->after('celestial_slug');
            $table->text('summary')->nullable()->after('celestial_kind');
        });

        $now = now();

        foreach (config('celestial-bodies', []) as $body) {
            DB::table('locations')->insert([
                'name' => $body['name'],
                'description' => implode("\n\n", $body['details']),
                'celestial_slug' => $body['slug'],
                'celestial_kind' => $body['kind'],
                'summary' => $body['summary'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('locations')->whereNotNull('celestial_slug')->delete();

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['celestial_slug']);
            $table->dropColumn(['celestial_slug', 'celestial_kind', 'summary']);
        });
    }
};
