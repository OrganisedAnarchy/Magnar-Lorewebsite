<?php

namespace Tests\Feature\Admin;

use App\Models\Language;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DailyTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_three_underrepresented_lore_categories_and_languages_are_excluded(): void
    {
        $entryCounts = [
            'cosmology' => 0,
            'culture' => 1,
            'history' => 2,
            'items' => 3,
            'magic' => 4,
            'nature' => 5,
            'organisations' => 6,
            'people' => 7,
            'religion' => 8,
            'technology' => 9,
        ];

        foreach ($entryCounts as $category => $count) {
            LoreEntry::factory()->count($count)->create(['category' => $category]);
        }

        Language::factory()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.daily-tasks.index'))
            ->assertOk()
            ->assertViewIs('admin.daily-tasks.index')
            ->assertViewHas('tasks', function (Collection $tasks): bool {
                $this->assertSame(['cosmology', 'culture', 'history'], $tasks->pluck('category')->all());
                $this->assertSame([0, 1, 2], $tasks->pluck('entries_count')->all());

                return true;
            })
            ->assertSee(route('admin.entries.create', ['category' => 'cosmology']))
            ->assertSee(route('admin.entries.create', ['category' => 'culture']))
            ->assertSee(route('admin.entries.create', ['category' => 'history']))
            ->assertSee(route('admin.daily-tasks.index'))
            ->assertSee('Daily Tasks');
    }

    public function test_guest_is_redirected_to_login_from_daily_tasks(): void
    {
        $this->get(route('admin.daily-tasks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_daily_tasks(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.daily-tasks.index'))
            ->assertForbidden();
    }
}
