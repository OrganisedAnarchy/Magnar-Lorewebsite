<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function person(string $title): LoreEntry
    {
        return LoreEntry::create(['title' => $title, 'category' => 'people']);
    }

    public function test_adding_an_unknown_relative_creates_the_person_and_inverse_link(): void
    {
        $aria = $this->person('Aria');

        $this->actingAs($this->admin())
            ->post(route('admin.entries.family.store', $aria), ['name' => 'Borin', 'relationship' => 'parent'])
            ->assertRedirect(route('admin.entries.edit', $aria));

        $borin = LoreEntry::where('title', 'Borin')->firstOrFail();
        $this->assertSame('people', $borin->category);
        $this->assertDatabaseHas('family_members', ['person_id' => $aria->id, 'relative_id' => $borin->id, 'relationship' => 'parent']);
        $this->assertDatabaseHas('family_members', ['person_id' => $borin->id, 'relative_id' => $aria->id, 'relationship' => 'child']);
    }

    public function test_existing_person_is_reused_case_insensitively(): void
    {
        $aria = $this->person('Aria');
        $borin = $this->person('Borin');

        $this->actingAs($this->admin())
            ->post(route('admin.entries.family.store', $aria), ['name' => 'borin', 'relationship' => 'sibling']);

        $this->assertSame(2, LoreEntry::count());
        $this->assertDatabaseHas('family_members', ['person_id' => $borin->id, 'relative_id' => $aria->id, 'relationship' => 'sibling']);
    }

    public function test_cannot_add_self_or_duplicate(): void
    {
        $aria = $this->person('Aria');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.entries.family.store', $aria), ['name' => 'Aria', 'relationship' => 'sibling'])
            ->assertSessionHasErrors('name');

        $this->post(route('admin.entries.family.store', $aria), ['name' => 'Borin', 'relationship' => 'spouse']);
        $this->post(route('admin.entries.family.store', $aria), ['name' => 'Borin', 'relationship' => 'spouse'])
            ->assertSessionHasErrors('name');

        $this->assertSame(2, FamilyMember::count());
    }

    public function test_removing_a_family_member_removes_both_links(): void
    {
        $aria = $this->person('Aria');
        $this->actingAs($this->admin())
            ->post(route('admin.entries.family.store', $aria), ['name' => 'Borin', 'relationship' => 'child']);

        $this->delete(route('admin.entries.family.destroy', [$aria, $aria->familyMembers()->first()]))
            ->assertRedirect(route('admin.entries.edit', $aria));

        $this->assertSame(0, FamilyMember::count());
        $this->get(route('admin.entries.edit', $aria))->assertOk();
    }

    public function test_only_people_can_have_family(): void
    {
        $org = LoreEntry::create(['title' => 'Guild', 'category' => 'organisations']);

        $this->actingAs($this->admin())
            ->post(route('admin.entries.family.store', $org), ['name' => 'X', 'relationship' => 'child'])
            ->assertNotFound();
    }
}
