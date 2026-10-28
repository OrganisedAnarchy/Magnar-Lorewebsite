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

    public function test_search_returns_matching_entries(): void
    {
        LoreEntry::factory()->create(['category' => 'magic', 'title' => 'Pyromancy']);
        LoreEntry::factory()->create(['category' => null, 'title' => 'Pyre Guard']);
        LoreEntry::factory()->create(['category' => 'nature', 'title' => 'Oakheart']);

        $this->actingAs($this->admin())
            ->getJson(route('admin.entries.search', ['q' => 'pyr']))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['title' => 'Pyromancy', 'category' => 'Magic & the Arcane'])
            ->assertJsonFragment(['title' => 'Pyre Guard', 'category' => 'Uncategorized']);
    }

    public function test_resolving_a_missing_link_creates_an_uncategorized_entry(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.entries.resolve'), ['title' => 'Brand New'])
            ->assertOk()
            ->assertJson(['created' => true]);

        $entry = LoreEntry::where('title', 'Brand New')->firstOrFail();
        $this->assertNull($entry->category);
        $this->assertSame(route('admin.entries.edit', $entry), $response->json('url'));
    }

    public function test_resolving_a_missing_link_with_a_category_uses_it(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.entries.resolve'), ['title' => 'Fireball', 'category' => 'magic & the arcane'])
            ->assertOk();

        $this->assertDatabaseHas('lore_entries', ['title' => 'Fireball', 'category' => 'magic']);
    }

    public function test_resolving_an_existing_link_does_not_create_a_duplicate(): void
    {
        $entry = LoreEntry::factory()->create(['category' => 'magic', 'title' => 'Fireball']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.entries.resolve'), ['title' => 'fireball'])
            ->assertOk()
            ->assertJson(['created' => false, 'url' => route('admin.entries.edit', $entry)]);

        $this->assertDatabaseCount('lore_entries', 1);
    }

    public function test_resolving_with_an_unknown_category_fails(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.entries.resolve'), ['title' => 'X', 'category' => 'nonsense'])
            ->assertStatus(422);

        $this->assertDatabaseCount('lore_entries', 0);
    }

    public function test_uncategorized_entry_cannot_be_saved_without_a_category(): void
    {
        $entry = LoreEntry::factory()->create(['category' => null]);

        $this->actingAs($this->admin())
            ->get(route('admin.entries.edit', $entry))
            ->assertOk()
            ->assertSee('This entry is uncategorized');

        $this->actingAs($this->admin())
            ->put(route('admin.entries.update', $entry), ['category' => '', 'title' => 'Still here'])
            ->assertSessionHasErrors('category');
    }

    public function test_check_reports_which_links_exist(): void
    {
        LoreEntry::factory()->create(['category' => 'magic', 'title' => 'Fireball']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.entries.check'), ['links' => [
                ['title' => 'fireball', 'category' => ''],
                ['title' => 'Fireball', 'category' => 'Magic & the Arcane'],
                ['title' => 'Fireball', 'category' => 'nature'],
                ['title' => 'Fireball', 'category' => 'nonsense'],
                ['title' => 'Missing'],
            ]])
            ->assertOk()
            ->assertExactJson(['results' => [true, true, false, false, false]]);

        $this->assertDatabaseCount('lore_entries', 1);
    }

    public function test_edit_page_embeds_link_statuses_for_the_description(): void
    {
        LoreEntry::factory()->create(['category' => 'magic', 'title' => 'Fireball']);
        $entry = LoreEntry::factory()->create(['description' => 'See [[Fireball|a spell]] and [[Ghost@nature]].']);

        $this->actingAs($this->admin())
            ->get(route('admin.entries.edit', $entry))
            ->assertOk()
            ->assertSee(e(json_encode([
                ['title' => 'Fireball', 'category' => '', 'exists' => true],
                ['title' => 'Ghost', 'category' => 'nature', 'exists' => false],
            ])), false);
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
