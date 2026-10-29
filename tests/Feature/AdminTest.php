<?php

namespace Tests\Feature;

use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_admin_can_save_people_dates_in_the_in_world_calendar(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Magnar',
                'dob_day' => 30,
                'dob_month' => 13,
                'dob_cycle' => 2,
                'dob_year' => 120,
                'dod_day' => 1,
                'dod_month' => 1,
                'dod_cycle' => 1,
                'dod_year' => 220,
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'category' => 'people',
            'title' => 'Magnar',
            'dob_day' => 30,
            'dob_month' => 13,
            'dob_cycle' => 2,
            'dob_year' => 120,
            'dod_day' => 1,
            'dod_month' => 1,
            'dod_cycle' => 1,
            'dod_year' => 220,
        ]);
    }

    public function test_admin_can_save_people_without_birth_or_death_dates(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Unknown Spirit',
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'category' => 'people',
            'title' => 'Unknown Spirit',
            'dob_day' => null,
            'dod_day' => null,
        ]);
    }

    public function test_non_people_entries_do_not_save_people_dates(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'magic',
                'title' => 'Fireball',
                'dob_day' => 12,
                'dob_month' => 3,
                'dob_cycle' => 1,
                'dob_year' => 100,
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'category' => 'magic',
            'title' => 'Fireball',
            'dob_day' => null,
            'dod_day' => null,
        ]);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function invalidDateComponents(): array
    {
        return [
            'day below range' => ['dob_day', 0],
            'day above range' => ['dob_day', 31],
            'month below range' => ['dob_month', 0],
            'month above range' => ['dob_month', 14],
            'cycle below range' => ['dob_cycle', 0],
            'cycle above range' => ['dob_cycle', 3],
            'negative year' => ['dob_year', -1],
            'death day above range' => ['dod_day', 31],
        ];
    }

    #[DataProvider('invalidDateComponents')]
    public function test_invalid_in_world_date_components_are_rejected(string $field, int $value): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Invalid Date',
                $field => $value,
            ])
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('lore_entries', 0);
    }

    public function test_in_world_dates_require_all_four_components_when_started(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Partial Date',
                'dob_day' => 12,
            ])
            ->assertSessionHasErrors(['dob_month', 'dob_cycle', 'dob_year']);

        $this->assertDatabaseCount('lore_entries', 0);
    }

    public function test_circa_dates_are_saved_with_their_components(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Old Sage',
                'dob_day' => 5,
                'dob_month' => 2,
                'dob_cycle' => 1,
                'dob_year' => 40,
                'dob_is_circa' => '1',
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'title' => 'Old Sage',
            'dob_year' => 40,
            'dob_is_circa' => true,
            'dod_is_circa' => false,
        ]);
    }

    public function test_circa_requires_the_date_components(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Vague Sage',
                'dod_is_circa' => '1',
            ])
            ->assertSessionHasErrors(['dod_day', 'dod_month', 'dod_cycle', 'dod_year']);

        $this->assertDatabaseCount('lore_entries', 0);
    }

    public function test_unknown_dates_are_saved_without_components_or_circa(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), [
                'category' => 'people',
                'title' => 'Lost Spirit',
                'dob_is_unknown' => '1',
                'dob_is_circa' => '1',
                'dob_day' => 9,
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'title' => 'Lost Spirit',
            'dob_is_unknown' => true,
            'dob_is_circa' => false,
            'dob_day' => null,
            'dob_year' => null,
        ]);
    }

    public function test_marking_a_date_unknown_clears_its_saved_components(): void
    {
        $entry = LoreEntry::factory()->create([
            'category' => 'people',
            'dod_day' => 3,
            'dod_month' => 4,
            'dod_cycle' => 2,
            'dod_year' => 90,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.entries.update', $entry), [
                'category' => 'people',
                'title' => $entry->title,
                'dod_is_unknown' => '1',
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'id' => $entry->id,
            'dod_is_unknown' => true,
            'dod_day' => null,
            'dod_year' => null,
        ]);
    }

    public function test_admin_can_update_people_dates(): void
    {
        $entry = LoreEntry::factory()->create(['category' => 'people']);

        $this->actingAs($this->admin())
            ->put(route('admin.entries.update', $entry), [
                'category' => 'people',
                'title' => $entry->title,
                'dob_day' => 4,
                'dob_month' => 6,
                'dob_cycle' => 1,
                'dob_year' => 80,
                'dod_day' => 22,
                'dod_month' => 9,
                'dod_cycle' => 2,
                'dod_year' => 170,
            ])
            ->assertRedirect(route('admin.entries.index'));

        $this->assertDatabaseHas('lore_entries', [
            'id' => $entry->id,
            'dob_day' => 4,
            'dob_month' => 6,
            'dob_cycle' => 1,
            'dob_year' => 80,
            'dod_day' => 22,
            'dod_month' => 9,
            'dod_cycle' => 2,
            'dod_year' => 170,
        ]);
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
