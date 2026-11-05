<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocationMapTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_admin_can_create_a_location_with_a_map(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.locations.store'), [
                'name' => 'Lomile',
                'description' => 'A port town.',
                'map' => UploadedFile::fake()->image('lomile.png', 400, 300),
            ])
            ->assertRedirect(route('admin.locations.index'));

        $location = Location::where('name', 'Lomile')->firstOrFail();
        $this->assertTrue($location->hasMap());
        Storage::disk('public')->assertExists($location->map_image_path);
    }

    public function test_places_lore_entries_are_moved_to_locations(): void
    {
        $oldPlaceId = DB::table('lore_entries')->insertGetId([
            'category' => 'places',
            'title' => 'Lomile',
            'description' => 'A port town.',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
        DB::table('lore_entries')->insert([
            'category' => 'places',
            'title' => 'North Reach',
            'description' => null,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
        $otherEntryId = DB::table('lore_entries')->insertGetId([
            'category' => 'magic',
            'title' => 'Fireball',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
        DB::table('sub_categories')->insert([
            'category' => 'places',
            'name' => 'City',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);

        $migration = require database_path('migrations/2026_11_01_000002_move_places_lore_entries_to_locations.php');
        $migration->up();

        $this->assertDatabaseCount('locations', 2);
        $this->assertDatabaseHas('locations', [
            'name' => 'Lomile',
            'description' => 'A port town.',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-02 00:00:00',
        ]);
        $this->assertDatabaseMissing('lore_entries', ['id' => $oldPlaceId]);
        $this->assertDatabaseHas('lore_entries', ['id' => $otherEntryId, 'category' => 'magic']);
        $this->assertDatabaseMissing('sub_categories', ['category' => 'places']);
    }

    public function test_child_needs_a_parent_with_a_map_and_a_pin(): void
    {
        $mapless = Location::factory()->create();
        $mapped = Location::factory()->withMap()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.locations.store'), ['name' => 'Inn', 'parent_id' => $mapless->id, 'pin_x' => 10, 'pin_y' => 10])
            ->assertSessionHasErrors('parent_id');
        $this->post(route('admin.locations.store'), ['name' => 'Inn', 'parent_id' => $mapped->id])
            ->assertSessionHasErrors(['pin_x', 'pin_y']);
        $this->post(route('admin.locations.store'), ['name' => 'Inn', 'parent_id' => $mapped->id, 'pin_x' => 12.5, 'pin_y' => 80])
            ->assertSessionHasNoErrors();

        $this->assertSame($mapped->id, Location::where('name', 'Inn')->firstOrFail()->parent_id);
    }

    public function test_location_cannot_be_moved_inside_its_own_descendant_or_lose_its_map_with_children(): void
    {
        $region = Location::factory()->withMap()->create();
        $town = Location::factory()->withMap()->pinnedOn($region)->create();
        $this->actingAs($this->admin());

        $this->put(route('admin.locations.update', $region), ['name' => $region->name, 'parent_id' => $town->id, 'pin_x' => 1, 'pin_y' => 1])
            ->assertSessionHasErrors('parent_id');
        $this->put(route('admin.locations.update', $region), ['name' => $region->name, 'remove_map' => 1])
            ->assertSessionHasErrors('map');
    }

    public function test_location_with_children_cannot_be_deleted(): void
    {
        $region = Location::factory()->withMap()->create();
        Location::factory()->pinnedOn($region)->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.locations.destroy', $region))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($region);
    }

    public function test_non_admins_cannot_manage_locations(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.locations.index'))
            ->assertForbidden();
    }

    public function test_public_endpoints_expose_pins_only_for_locations_with_maps(): void
    {
        $region = Location::factory()->withMap()->create(['name' => 'Region']);
        $town = Location::factory()->pinnedOn($region, 25, 75)->create(['name' => 'Town', 'description' => 'Quiet.']);

        $this->getJson(route('map.locations.index'))
            ->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.has_map', true);

        $this->getJson(route('map.locations.show', $region))
            ->assertOk()
            ->assertJsonPath('children.0.name', 'Town')
            ->assertJsonPath('children.0.pin_x', 25)
            ->assertJsonPath('children.0.description', 'Quiet.')
            ->assertJsonPath('children.0.has_map', false);

        $this->getJson(route('map.locations.show', $town))
            ->assertOk()
            ->assertJsonPath('map_url', null)
            ->assertJsonPath('children', [])
            ->assertJsonPath('breadcrumbs.0.name', 'Region');

        $this->get(route('map'))->assertOk();
    }
}
