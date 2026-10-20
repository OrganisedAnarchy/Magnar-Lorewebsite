<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_can_have_multiple_reusable_types(): void
    {
        $timestamp = now();
        $innTypeId = DB::table('place_types')->insertGetId([
            'name' => 'Inn',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $governmentBuildingTypeId = DB::table('place_types')->insertGetId([
            'name' => 'Government Building',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $firstPlaceId = DB::table('places')->insertGetId([
            'name' => 'The Silver Stag',
            'category' => 'building',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $secondPlaceId = DB::table('places')->insertGetId([
            'name' => 'The Golden Hart',
            'category' => 'building',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('place_place_type')->insert([
            ['place_id' => $firstPlaceId, 'place_type_id' => $innTypeId],
            ['place_id' => $firstPlaceId, 'place_type_id' => $governmentBuildingTypeId],
            ['place_id' => $secondPlaceId, 'place_type_id' => $innTypeId],
        ]);

        $this->assertDatabaseCount('place_types', 2);
        $this->assertDatabaseCount('place_place_type', 3);
        $this->assertDatabaseHas('place_place_type', [
            'place_id' => $firstPlaceId,
            'place_type_id' => $governmentBuildingTypeId,
        ]);
        $this->assertDatabaseHas('place_place_type', [
            'place_id' => $secondPlaceId,
            'place_type_id' => $innTypeId,
        ]);
    }

    public function test_deleting_a_place_removes_its_type_assignments_without_deleting_types(): void
    {
        $timestamp = now();
        $placeTypeId = DB::table('place_types')->insertGetId([
            'name' => 'Natural Landmark',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $placeId = DB::table('places')->insertGetId([
            'name' => 'Whispering Falls',
            'category' => 'natural_location',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        DB::table('place_place_type')->insert([
            'place_id' => $placeId,
            'place_type_id' => $placeTypeId,
        ]);

        DB::table('places')->where('id', $placeId)->delete();

        $this->assertDatabaseMissing('place_place_type', [
            'place_id' => $placeId,
            'place_type_id' => $placeTypeId,
        ]);
        $this->assertDatabaseHas('place_types', [
            'id' => $placeTypeId,
            'name' => 'Natural Landmark',
        ]);
    }
}
