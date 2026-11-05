<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Language;
use App\Models\Location;
use App\Models\LoreEntry;
use App\Models\OrganisationRank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjaxResponsesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_creating_an_entry_returns_json_for_the_toast_and_the_update_url(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.entries.store'), ['category' => 'people', 'title' => 'Aria'])
            ->assertOk()
            ->assertJsonPath('message', 'Entry created.')
            ->assertJsonPath('method', 'PUT');

        $entry = LoreEntry::where('title', 'Aria')->firstOrFail();
        $response->assertJsonPath('id', $entry->id)
            ->assertJsonPath('action', route('admin.entries.update', $entry))
            ->assertJsonPath('edit_url', route('admin.entries.edit', $entry));
    }

    public function test_updating_an_entry_returns_json_without_redirecting(): void
    {
        $entry = LoreEntry::factory()->create(['category' => 'magic']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.entries.update', $entry), ['category' => 'magic', 'title' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('message', 'Entry updated.');

        $this->assertSame('Renamed', $entry->fresh()->title);
    }

    public function test_validation_failures_return_422_json_errors(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.entries.store'), ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'title']);
    }

    public function test_deleting_each_resource_returns_json(): void
    {
        $admin = $this->actingAs($this->admin());
        $entry = LoreEntry::factory()->create();
        $book = Book::factory()->create();
        $language = Language::factory()->create();
        $location = Location::factory()->create();

        $admin->deleteJson(route('admin.entries.destroy', $entry))->assertOk()->assertJsonPath('message', 'Entry deleted.');
        $admin->deleteJson(route('admin.books.destroy', $book))->assertOk()->assertJsonPath('message', 'Book deleted.');
        $admin->deleteJson(route('admin.languages.destroy', $language))->assertOk()->assertJsonPath('message', 'Language deleted.');
        $admin->deleteJson(route('admin.locations.destroy', $location))->assertOk()->assertJsonPath('message', 'Location deleted.');

        $this->assertModelMissing($entry);
        $this->assertModelMissing($book);
        $this->assertModelMissing($language);
        $this->assertModelMissing($location);
    }

    public function test_deleting_a_location_with_children_is_rejected_as_json(): void
    {
        $parent = Location::factory()->withMap()->create();
        Location::factory()->pinnedOn($parent)->create();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.locations.destroy', $parent))
            ->assertUnprocessable()
            ->assertJsonPath('message', "Move or delete this location's children first.");

        $this->assertModelExists($parent);
    }

    public function test_creating_a_book_language_and_location_returns_their_update_urls(): void
    {
        $admin = $this->actingAs($this->admin());

        $admin->postJson(route('admin.books.store'), ['title' => 'Tome', 'languages' => ['Common']])
            ->assertOk()
            ->assertJsonPath('method', 'PUT')
            ->assertJsonPath('action', route('admin.books.update', Book::where('title', 'Tome')->firstOrFail()));

        $admin->postJson(route('admin.languages.store'), ['name' => 'Elvish'])
            ->assertOk()
            ->assertJsonPath('action', route('admin.languages.update', Language::where('name', 'Elvish')->firstOrFail()));

        $admin->postJson(route('admin.locations.store'), ['name' => 'Harbor'])
            ->assertOk()
            ->assertJsonPath('action', route('admin.locations.update', Location::where('name', 'Harbor')->firstOrFail()))
            ->assertJsonPath('map_url', null);
    }

    public function test_family_and_organisation_changes_return_json(): void
    {
        $admin = $this->actingAs($this->admin());
        $person = LoreEntry::create(['title' => 'Aria', 'category' => 'people']);
        $guild = LoreEntry::create(['title' => 'Guild', 'category' => 'organisations']);

        $admin->postJson(route('admin.entries.family.store', $person), ['name' => 'Borin', 'relationship' => 'parent'])
            ->assertOk()
            ->assertJsonPath('message', 'Family member added.')
            ->assertJsonCount(1, 'extras.family.members')
            ->assertJsonPath('extras.family.members.0.name', 'Borin');

        $memberId = $person->familyMembers()->firstOrFail()->id;
        $admin->putJson(route('admin.entries.family.update', [$person, $memberId]), ['relationship' => 'child'])
            ->assertOk()
            ->assertJsonPath('extras.family.members.0.label', 'Child');

        $admin->deleteJson(route('admin.entries.family.destroy', [$person, $memberId]))
            ->assertOk()
            ->assertJsonCount(0, 'extras.family.members');

        $admin->postJson(route('admin.entries.family.store', $person), ['name' => 'Borin', 'relationship' => 'parent'])
            ->assertOk();

        $admin->postJson(route('admin.entries.family.store', $person), ['name' => 'aria', 'relationship' => 'parent'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $admin->postJson(route('admin.entries.ranks.store', $guild), ['name' => 'Master', 'level' => 1])
            ->assertOk()
            ->assertJsonPath('message', 'Rank added.')
            ->assertJsonPath('extras.organisation.ranks.0.name', 'Master')
            ->assertJsonPath('extras.organisation.next_level', 2);

        $rank = OrganisationRank::firstOrFail();
        $admin->deleteJson(route('admin.entries.ranks.destroy', [$guild, $rank]))
            ->assertOk()
            ->assertJsonPath('message', 'Rank removed.')
            ->assertJsonCount(0, 'extras.organisation.ranks');

        $admin->postJson(route('admin.entries.members.store', $guild), ['member_id' => $person->id, 'is_current' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Member added.')
            ->assertJsonPath('extras.organisation.members.0.name', 'Aria');

        $membershipId = $guild->members()->firstOrFail()->id;
        $admin->deleteJson(route('admin.entries.members.destroy', [$guild, $membershipId]))
            ->assertOk()
            ->assertJsonCount(0, 'extras.organisation.members');
    }

    public function test_plain_requests_still_redirect_with_a_flash_message(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.store'), ['category' => 'magic', 'title' => 'Fireball'])
            ->assertRedirect(route('admin.entries.index'))
            ->assertSessionHas('status', 'Entry created.');
    }

    public function test_admin_layout_exposes_the_toast_host_with_flashed_status(): void
    {
        $this->actingAs($this->admin())
            ->withSession(['status' => 'Entry created.'])
            ->get(route('admin.entries.index'))
            ->assertSee('id="toast-host"', false)
            ->assertSee('Entry created.');
    }
}
