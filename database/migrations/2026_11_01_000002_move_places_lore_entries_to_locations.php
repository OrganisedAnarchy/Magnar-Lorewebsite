<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('lore_entries')
                ->where('category', 'places')
                ->orderBy('id')
                ->chunkById(200, function (Collection $entries): void {
                    DB::table('locations')->insert(
                        $entries->map(static fn (object $entry): array => [
                            'name' => $entry->title,
                            'description' => $entry->description,
                            'created_at' => $entry->created_at,
                            'updated_at' => $entry->updated_at,
                        ])->all()
                    );

                    DB::table('lore_entries')->whereIn('id', $entries->pluck('id'))->delete();
                });

            DB::table('sub_categories')->where('category', 'places')->delete();
        });
    }

    public function down(): void
    {
        throw new LogicException('Moving lore entries to locations cannot be reversed automatically.');
    }
};
