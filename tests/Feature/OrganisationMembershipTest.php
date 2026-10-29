<?php

namespace Tests\Feature;

use App\Models\LoreEntry;
use App\Models\OrganisationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function organisation(): LoreEntry
    {
        return LoreEntry::create(['title' => 'The Guild', 'category' => 'organisations']);
    }

    private function person(string $title = 'Aria'): LoreEntry
    {
        return LoreEntry::create(['title' => $title, 'category' => 'people']);
    }

    public function test_admin_can_add_ranks_to_an_organisation(): void
    {
        $org = $this->organisation();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.entries.ranks.store', $org), ['name' => 'Master', 'level' => 1])
            ->assertRedirect(route('admin.entries.edit', $org));

        $this->assertDatabaseHas('organisation_ranks', ['organisation_id' => $org->id, 'name' => 'Master', 'level' => 1]);
        $this->actingAs($admin)->get(route('admin.entries.edit', $org))->assertOk()->assertSee('Master');
    }

    public function test_ranks_are_only_for_organisations(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.entries.ranks.store', $this->person()), ['name' => 'Master', 'level' => 1])
            ->assertNotFound();
    }

    public function test_admin_can_add_a_member_with_rank_and_world_date(): void
    {
        $org = $this->organisation();
        $rank = $org->ranks()->create(['name' => 'Master', 'level' => 1]);
        $person = $this->person();

        $this->actingAs($this->admin())->post(route('admin.entries.members.store', $org), [
            'member_id' => $person->id,
            'organisation_rank_id' => $rank->id,
            'join_day' => 12,
            'join_month' => 13,
            'join_cycle' => 2,
            'join_year' => 340,
            'is_current' => 1,
        ])->assertRedirect(route('admin.entries.edit', $org));

        $member = OrganisationMember::first();
        $this->assertTrue($member->is_current);
        $this->assertSame('12-13-2-340', $member->joinDateLabel());
        $this->assertSame($rank->id, $member->organisation_rank_id);
    }

    public function test_member_can_be_marked_as_former(): void
    {
        $org = $this->organisation();
        $member = $org->members()->create(['member_id' => $this->person()->id, 'is_current' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.entries.members.update', [$org, $member]), [])
            ->assertRedirect();

        $this->assertFalse($member->fresh()->is_current);
    }

    public function test_member_must_be_a_person_and_unique(): void
    {
        $org = $this->organisation();
        $person = $this->person();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.entries.members.store', $org), ['member_id' => $org->id])
            ->assertSessionHasErrors('member_id');

        $this->actingAs($admin)->post(route('admin.entries.members.store', $org), ['member_id' => $person->id]);
        $this->actingAs($admin)
            ->post(route('admin.entries.members.store', $org), ['member_id' => $person->id])
            ->assertSessionHasErrors('member_id');
    }

    public function test_rank_must_belong_to_the_organisation_and_date_must_be_complete(): void
    {
        $org = $this->organisation();
        $other = LoreEntry::create(['title' => 'Other', 'category' => 'organisations']);
        $foreignRank = $other->ranks()->create(['name' => 'Chief', 'level' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.entries.members.store', $org), [
                'member_id' => $this->person()->id,
                'organisation_rank_id' => $foreignRank->id,
                'join_year' => 10,
            ])
            ->assertSessionHasErrors(['organisation_rank_id', 'join_day', 'join_month', 'join_cycle']);
    }

    public function test_non_admins_cannot_manage_members(): void
    {
        $org = $this->organisation();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.entries.members.store', $org), ['member_id' => $this->person()->id])
            ->assertForbidden();
    }
}
