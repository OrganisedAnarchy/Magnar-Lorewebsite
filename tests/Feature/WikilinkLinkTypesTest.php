<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Language;
use App\Models\Location;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikilinkLinkTypesTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_with_location_language_and_book_categories_are_routed_to_their_own_tables(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $location = Location::factory()->create(['name' => 'Highvale']);
        $language = Language::factory()->create(['name' => 'Old Tongue']);
        $book = Book::factory()->create(['title' => 'The Long Night']);

        $this->actingAs($admin)->postJson(route('admin.entries.check'), ['links' => [
            ['title' => 'highvale', 'category' => 'Location'],
            ['title' => 'Old Tongue', 'category' => 'Languages'],
            ['title' => 'Nope', 'category' => 'Books'],
        ]])->assertOk()->assertExactJson(['results' => [true, true, false]]);

        $this->postJson(route('admin.entries.resolve'), ['title' => 'Highvale', 'category' => 'Locations'])
            ->assertOk()->assertJson(['created' => false, 'url' => route('admin.locations.edit', $location)]);

        $this->postJson(route('admin.entries.resolve'), ['title' => 'Old Tongue', 'category' => 'language'])
            ->assertJson(['url' => route('admin.languages.edit', $language)]);

        $this->postJson(route('admin.entries.resolve'), ['title' => 'The Long Night', 'category' => 'Book'])
            ->assertJson(['url' => route('admin.books.edit', $book)]);
    }

    public function test_resolving_a_link_with_a_heading_appends_it_to_the_page_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $book = Book::factory()->create(['title' => 'The Long Night']);
        $entry = LoreEntry::factory()->create(['title' => 'Blah']);

        $this->actingAs($admin)->postJson(route('admin.entries.resolve'), ['title' => 'Blah', 'heading' => 'Old Lands', 'category' => null])
            ->assertOk()->assertJson(['url' => route('admin.entries.edit', $entry).'#Old%20Lands']);

        $this->postJson(route('admin.entries.resolve'), ['title' => 'The Long Night', 'heading' => 'Chapter 1', 'category' => 'Books'])
            ->assertJson(['url' => route('admin.books.edit', $book).'#Chapter%201']);

        $this->postJson(route('admin.entries.resolve'), ['title' => 'Blah', 'category' => null])
            ->assertJson(['url' => route('admin.entries.edit', $entry)]);
    }

    public function test_wikilinks_split_title_heading_alias_and_category(): void
    {
        $this->assertSame(
            ['title' => 'Blah', 'heading' => 'Foo', 'alias' => 'Shown', 'category' => 'Books'],
            LoreEntry::parseWikilink('Blah#Foo|Shown@Books'),
        );
        $this->assertNull(LoreEntry::parseWikilink('Blah')['heading']);
        $this->assertSame('C#', LoreEntry::parseWikilink('C#')['title']);

        LoreEntry::factory()->create(['title' => 'Blah']);
        $this->assertSame(
            [['title' => 'Blah', 'category' => '', 'exists' => true]],
            LoreEntry::linkStatuses('[[Blah#Foo]] and [[Blah#Bar|again]]'),
        );
    }

    public function test_resolving_a_missing_link_creates_it_in_the_right_table(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->postJson(route('admin.entries.resolve'), ['title' => 'Mistfall', 'category' => 'Locations'])
            ->assertOk()->assertJson(['created' => true]);
        $this->postJson(route('admin.entries.resolve'), ['title' => 'Elvish', 'category' => 'Languages'])->assertOk();
        $this->postJson(route('admin.entries.resolve'), ['title' => 'Tome of Ash', 'category' => 'Books'])->assertOk();

        $this->assertDatabaseHas('locations', ['name' => 'Mistfall']);
        $this->assertDatabaseHas('languages', ['name' => 'Elvish']);
        $this->assertDatabaseHas('books', ['title' => 'Tome of Ash']);
        $this->assertDatabaseMissing('lore_entries', ['title' => 'Mistfall']);
    }

    public function test_search_with_a_link_type_returns_matching_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Location::factory()->create(['name' => 'Highvale']);

        $this->actingAs($admin)->getJson(route('admin.entries.search', ['q' => 'High', 'category' => 'Locations']))
            ->assertOk()->assertExactJson([['title' => 'Highvale', 'category' => 'Locations']]);
    }
}
