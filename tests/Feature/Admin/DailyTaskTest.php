<?php

namespace Tests\Feature\Admin;

use App\Models\Language;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.entries.index'))
            ->assertOk()
            ->assertSeeInOrder(['Cosmology', 'Culture & Society', 'History & Events'])
            ->assertSee('0 entries currently in this category.')
            ->assertSee('1 entry currently in this category.')
            ->assertSee('2 entries currently in this category.')
            ->assertSee(route('admin.entries.create', ['category' => 'cosmology']))
            ->assertSee(route('admin.entries.create', ['category' => 'culture']))
            ->assertSee(route('admin.entries.create', ['category' => 'history']))
            ->assertDontSee(route('admin.entries.create', ['category' => 'items']))
            ->assertDontSee(route('admin.entries.create', ['category' => 'languages']))
            ->assertSee('fa-clipboard-list')
            ->assertSee('aria-label="Daily Tasks"', false)
            ->assertSee('Daily Tasks');

        $this->assertSame(3, substr_count($response->getContent(), 'currently in this category.'));
    }

    public function test_daily_tasks_tray_is_available_on_other_admin_pages(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('aria-label="Daily Tasks"', false)
            ->assertSee(route('admin.entries.create', ['category' => 'cosmology']));
    }

    public function test_previous_daily_tasks_url_redirects_to_the_open_tray(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $destination = route('admin.entries.index', ['tray' => 'daily-tasks']);

        $this->actingAs($admin)->get(route('admin.daily-tasks.index'))
            ->assertRedirect($destination);

        $this->actingAs($admin)->get($destination)
            ->assertOk()
            ->assertSee('<details  open >', false);
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
