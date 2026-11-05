<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTypeTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $this->actingAs($admin);
    }

    public function test_a_location_can_be_given_a_type(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.locations.store'), ['name' => 'Aldoria', 'location_type' => 'Country'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Aldoria', 'is_celestial_body' => false, 'location_type' => 'Country']);
    }

    public function test_an_unknown_location_type_is_rejected(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.locations.store'), ['name' => 'Aldoria', 'location_type' => 'Galaxy'])
            ->assertSessionHasErrors('location_type');
    }

    public function test_building_and_settlement_subtypes_are_kept_only_for_their_location_type(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.locations.store'), ['name' => 'The Gilded Hall', 'location_type' => 'Building', 'building_type' => 'Tavern', 'settlement_type' => 'Monastery'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'The Gilded Hall', 'building_type' => 'Tavern', 'settlement_type' => null]);

        $this->post(route('admin.locations.store'), ['name' => 'Saint Orrin', 'location_type' => 'Religious Settlement', 'building_type' => 'Tavern', 'settlement_type' => 'Monastery'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Saint Orrin', 'building_type' => null, 'settlement_type' => 'Monastery']);

        $this->post(route('admin.locations.store'), ['name' => 'Rivermouth', 'location_type' => 'Town', 'building_type' => 'Tavern'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Rivermouth', 'building_type' => null]);
    }

    public function test_a_custom_celestial_body_needs_a_kind_and_summary_and_has_no_location_type(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.locations.store'), ['name' => 'Nyx', 'is_celestial_body' => '1', 'location_type' => 'City'])
            ->assertSessionHasErrors(['summary', 'celestial_kind']);

        $this->post(route('admin.locations.store'), [
            'name' => 'Nyx', 'is_celestial_body' => '1', 'celestial_kind' => 'Moon', 'summary' => 'A dark moon.', 'location_type' => 'City',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Nyx', 'is_celestial_body' => true, 'celestial_kind' => 'Moon', 'location_type' => null, 'celestial_slug' => null]);
        $this->assertTrue(Location::where('name', 'Nyx')->firstOrFail()->isCelestialBody());
    }

    public function test_unchecking_celestial_body_clears_its_celestial_fields(): void
    {
        $nyx = Location::factory()->create(['is_celestial_body' => true, 'celestial_kind' => 'Moon', 'summary' => 'A dark moon.']);

        $this->actingAsAdmin()
            ->put(route('admin.locations.update', $nyx), ['name' => 'Nyx', 'is_celestial_body' => '0', 'location_type' => 'City'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['id' => $nyx->id, 'is_celestial_body' => false, 'celestial_kind' => null, 'summary' => null, 'location_type' => 'City']);
    }

    public function test_chart_bodies_always_remain_celestial_bodies(): void
    {
        $magnar = Location::where('celestial_slug', 'magnar')->firstOrFail();

        $this->actingAsAdmin()
            ->put(route('admin.locations.update', $magnar), ['name' => 'Magnar', 'is_celestial_body' => '0', 'location_type' => 'City', 'summary' => 'Home.', 'celestial_kind' => 'Planet'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('locations', ['id' => $magnar->id, 'is_celestial_body' => true, 'location_type' => null]);
    }
}
