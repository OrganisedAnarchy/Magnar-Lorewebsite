<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CelestialBodyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_celestial_bodies_are_seeded_as_locations(): void
    {
        $this->assertSame(count(config('celestial-bodies')), Location::celestialBodies()->count());
        $this->assertDatabaseHas('locations', ['celestial_slug' => 'helia', 'celestial_kind' => 'Sun', 'name' => 'Helia']);
    }

    public function test_admin_can_edit_a_celestial_body_and_the_public_page_reflects_it(): void
    {
        $aegis = Location::where('celestial_slug', 'aegis')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.locations.update', $aegis), [
                'name' => 'Aegis Prime',
                'summary' => 'A new summary.',
                'celestial_kind' => 'Moon',
                'description' => "First paragraph.\n\nSecond paragraph.",
            ])
            ->assertRedirect(route('admin.locations.index'));

        $this->get(route('celestial-bodies.show', 'aegis'))
            ->assertOk()
            ->assertSee('Aegis Prime')
            ->assertSee('A new summary.')
            ->assertSee('<p>First paragraph.</p>', false)
            ->assertSee('<p>Second paragraph.</p>', false);

        $this->get(route('home'))->assertSee('Aegis Prime');
        $this->assertSame('aegis', $aegis->fresh()->celestial_slug);
    }

    public function test_celestial_body_requires_a_summary_and_valid_kind_and_cannot_be_nested_or_deleted(): void
    {
        $magnar = Location::where('celestial_slug', 'magnar')->firstOrFail();
        $other = Location::factory()->withMap()->create();
        $this->actingAs($this->admin());

        $this->put(route('admin.locations.update', $magnar), ['name' => 'Magnar', 'summary' => '', 'celestial_kind' => 'Comet'])
            ->assertSessionHasErrors(['summary', 'celestial_kind']);

        $this->put(route('admin.locations.update', $magnar), [
            'name' => 'Magnar', 'summary' => 'Home.', 'celestial_kind' => 'Planet', 'parent_id' => $other->id, 'pin_x' => 5, 'pin_y' => 5,
        ])->assertSessionHasNoErrors();
        $this->assertNull($magnar->fresh()->parent_id);

        $this->delete(route('admin.locations.destroy', $magnar))->assertSessionHasErrors('delete');
        $this->assertModelExists($magnar);
    }

    public function test_celestial_body_with_a_map_can_be_the_parent_of_locations(): void
    {
        $magnar = Location::where('celestial_slug', 'magnar')->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.locations.store'), ['name' => 'Lomile', 'parent_id' => $magnar->id, 'pin_x' => 10, 'pin_y' => 10])
            ->assertSessionHasErrors('parent_id');

        $magnar->update(['map_image_path' => 'maps/magnar.png']);

        $this->post(route('admin.locations.store'), ['name' => 'Lomile', 'parent_id' => $magnar->id, 'pin_x' => 10, 'pin_y' => 10])
            ->assertSessionHasNoErrors();

        $this->get(route('celestial-bodies.show', 'magnar'))->assertOk()->assertSee('Lomile');
        $this->getJson(route('map.locations.index'))->assertJsonPath('locations.0.name', 'Magnar')->assertJsonCount(1, 'locations');
        $this->getJson(route('map.locations.show', Location::where('name', 'Lomile')->firstOrFail()))
            ->assertJsonPath('breadcrumbs.0.name', 'Magnar');
    }

    public function test_the_welcome_page_provides_celestial_lore_to_the_interactive_system(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-celestial-bodies=', false)
            ->assertSee('Aegis')
            ->assertSee('Helia')
            ->assertSee('Ogunja')
            ->assertSee('Amial')
            ->assertSee('Hanar')
            ->assertSee('Misana')
            ->assertSee('Lysus');
    }

    public function test_each_celestial_body_page_shows_its_lore_and_a_return_link(): void
    {
        foreach (config('celestial-bodies') as $body) {
            $this->get(route('celestial-bodies.show', $body['slug']))
                ->assertOk()
                ->assertSee("{$body['name']} · The World of Magnar")
                ->assertSee($body['name'])
                ->assertSee($body['summary'])
                ->assertSee('Return to the system');
        }
    }

    public function test_helia_page_explains_the_seasonal_cycle_and_magnar_calendar(): void
    {
        $this->get(route('celestial-bodies.show', 'helia'))
            ->assertOk()
            ->assertSee('780 days')
            ->assertSee('two 390-day cycles')
            ->assertSee('13 months of 30 days')
            ->assertSee('195 days')
            ->assertSee('Ogunja, Amial, Hanar, and Misana');
    }

    public function test_unknown_celestial_body_pages_return_not_found(): void
    {
        $this->get(route('celestial-bodies.show', 'unknown-body'))
            ->assertNotFound();

        $this->get(route('celestial-bodies.show', 'magnar.name'))
            ->assertNotFound();
    }
}
