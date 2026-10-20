<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_places_can_be_created_with_parent_and_reusable_type_relationships(): void
    {
        $parent = Place::create([
            'name' => 'Lomile',
            'category' => 'populated_area',
        ]);
        $child = Place::create([
            'name' => 'The Silver Stag',
            'category' => 'building',
            'parent_id' => $parent->id,
            'map_image_path' => 'maps/silver-stag.png',
        ]);
        $innType = PlaceType::create(['name' => 'Inn']);
        $child->placeTypes()->attach($innType);

        $this->assertSame($parent->id, $child->parent->id);
        $this->assertSame($child->id, $parent->children->sole()->id);
        $this->assertSame($innType->id, $child->placeTypes->sole()->id);
        $this->assertSame($child->id, $innType->places->sole()->id);
        $this->assertDatabaseHas('places', [
            'id' => $child->id,
            'name' => 'The Silver Stag',
            'map_image_path' => 'maps/silver-stag.png',
        ]);
    }
}
