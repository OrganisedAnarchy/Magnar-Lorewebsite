<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoreTimelineSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_can_have_no_lore_subject_or_multiple_subjects_of_different_categories(): void
    {
        $timestamp = now();
        $placeId = DB::table('places')->insertGetId([
            'name' => 'Lomile',
            'category' => 'populated_area',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subjectIds = [];

        foreach (['location', 'people', 'organisation'] as $category) {
            $subjectIds[] = DB::table('lore_subjects')->insertGetId([
                'category' => $category,
                'name' => ucfirst($category).' subject',
                'overview' => 'A lore subject overview.',
                'place_id' => $category === 'location' ? $placeId : null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $unassociatedEventId = DB::table('events')->insertGetId([
            'title' => 'An unassociated event',
            'description' => 'This event has no subject associations.',
            'world_year' => 1,
            'cycle' => 1,
            'month' => 1,
            'day' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $associatedEventId = DB::table('events')->insertGetId([
            'title' => 'The fall of Lomile',
            'description' => 'This event concerns several lore subjects.',
            'world_year' => 2,
            'cycle' => 1,
            'month' => 3,
            'day' => 12,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('event_lore_subject')->insert(array_map(
            fn (int $subjectId): array => [
                'event_id' => $associatedEventId,
                'lore_subject_id' => $subjectId,
            ],
            $subjectIds,
        ));

        $this->assertDatabaseHas('events', ['id' => $unassociatedEventId]);
        $this->assertDatabaseMissing('event_lore_subject', ['event_id' => $unassociatedEventId]);
        $this->assertDatabaseCount('event_lore_subject', 3);
        foreach ($subjectIds as $subjectId) {
            $this->assertDatabaseHas('event_lore_subject', [
                'event_id' => $associatedEventId,
                'lore_subject_id' => $subjectId,
            ]);
        }
    }

    public function test_people_can_store_required_birth_dates_and_optional_approximate_death_dates(): void
    {
        $timestamp = now();
        $personId = DB::table('lore_subjects')->insertGetId([
            'category' => 'people',
            'name' => 'Mara Vale',
            'overview' => 'A local historian.',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('people_details')->insert([
            'lore_subject_id' => $personId,
            'birth_world_year' => 104,
            'birth_cycle' => 2,
            'birth_month' => 7,
            'birth_day' => 19,
            'birth_date_is_approximate' => true,
            'death_world_year' => null,
            'death_cycle' => null,
            'death_month' => null,
            'death_day' => null,
            'death_date_is_approximate' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->assertDatabaseHas('people_details', [
            'lore_subject_id' => $personId,
            'birth_world_year' => 104,
            'birth_cycle' => 2,
            'birth_month' => 7,
            'birth_day' => 19,
            'birth_date_is_approximate' => true,
            'death_world_year' => null,
            'death_cycle' => null,
            'death_month' => null,
            'death_day' => null,
            'death_date_is_approximate' => false,
        ]);
    }

    public function test_people_cannot_be_saved_without_a_birth_date(): void
    {
        $timestamp = now();
        $personId = DB::table('lore_subjects')->insertGetId([
            'category' => 'people',
            'name' => 'Mara Vale',
            'overview' => 'A local historian.',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $this->expectException(QueryException::class);

        DB::table('people_details')->insert([
            'lore_subject_id' => $personId,
        ]);
    }
}
