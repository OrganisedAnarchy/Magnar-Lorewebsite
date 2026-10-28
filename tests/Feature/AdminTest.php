<?php

namespace Tests\Feature;

use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.entries.index'))->assertRedirect(route('login'));
    }

    public function test_non_admins_are_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.entries.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_an_entry(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'magic',
                'title' => 'Fireball',
                'description' => "# Fireball\n\nSee [[Pyromancy]].",
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', ['category' => 'magic', 'title' => 'Fireball']);
    }

    public function test_entry_category_and_title_are_validated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), ['category' => 'bogus', 'title' => ''])
            ->assertSessionHasErrors(['category', 'title']);
    }

    public function test_admin_can_update_and_delete_an_entry(): void
    {
        $entry = LoreEntry::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.entries.update', $entry), [
                'category' => 'cosmology',
                'title' => 'Renamed',
                'description' => 'New text',
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', ['id' => $entry->id, 'category' => 'cosmology', 'title' => 'Renamed']);

        $this->actingAs($admin)->delete(route('admin.entries.destroy', $entry));
        $this->assertDatabaseMissing('lore_entries', ['id' => $entry->id]);
    }

    public function test_index_can_be_filtered_by_category(): void
    {
        LoreEntry::factory()->create(['category' => 'magic', 'title' => 'Fireball']);
        LoreEntry::factory()->create(['category' => 'nature', 'title' => 'Oakheart']);

        $this->actingAs($this->admin())
            ->get(route('admin.entries.index', ['category' => 'magic']))
            ->assertOk()
            ->assertSee('Fireball')
            ->assertDontSee('Oakheart');
    }

    public function test_admin_pages_render(): void
    {
        $entry = LoreEntry::factory()->create();
        $this->actingAs($this->admin());

        $this->get(route('admin.entries.index'))->assertOk();
        $this->get(route('admin.entries.create', ['category' => 'magic']))->assertOk();
        $this->get(route('admin.entries.edit', $entry))->assertOk();
    }
}
