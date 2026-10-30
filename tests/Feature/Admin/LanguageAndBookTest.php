<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Language;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageAndBookTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_manage_languages_with_phonology_syllables_lexicon_and_notes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.languages.store'), [
            'name' => 'Old Tongue',
            'phonemes' => ['p', 'a', 'a', 'ʃ'],
            'syllables' => ['ka', 'to', 'ka'],
            'grammar' => 'SOV',
            'writing_system' => 'Runic',
            'words' => [
                ['word' => 'kato', 'meaning' => 'sun'],
                ['word' => 'toka', 'meaning' => 'moon', 'notes' => 'poetic'],
            ],
        ])->assertRedirect(route('admin.languages.index'));

        $language = Language::firstOrFail();
        $this->assertSame(['p', 'a', 'ʃ'], $language->phonemes);
        $this->assertSame(['ka', 'to'], $language->syllables);
        $this->assertSame('Runic', $language->writing_system);
        $this->assertCount(2, $language->words);

        $this->actingAs($admin)->put(route('admin.languages.update', $language), [
            'name' => 'Elder Tongue',
            'words' => [['word' => 'mi', 'meaning' => 'water']],
        ])->assertRedirect(route('admin.languages.index'));

        $language->refresh();
        $this->assertSame('Elder Tongue', $language->name);
        $this->assertSame(['mi'], $language->words->pluck('word')->all());

        $this->actingAs($admin)->get(route('admin.languages.edit', $language))->assertOk();
        $this->actingAs($admin)->get(route('admin.languages.index'))->assertOk()->assertSee('Elder Tongue');

        $this->actingAs($admin)->delete(route('admin.languages.destroy', $language));
        $this->assertDatabaseCount('languages', 0);
        $this->assertDatabaseCount('language_words', 0);
    }

    public function test_language_words_require_a_word(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.languages.store'), ['name' => 'X', 'words' => [['meaning' => 'sun']]])
            ->assertSessionHasErrors('words.0.word');
    }

    public function test_book_with_new_author_creates_a_placeholder_person(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'The Lost Annals',
            'author_name' => 'Verin Ashdale',
            'languages' => ['Common', 'Elvish', 'common'],
            'summary' => 'A history.',
            'published_day' => 3,
            'published_month' => 4,
            'published_cycle' => 1,
            'published_year' => 120,
        ])->assertRedirect(route('admin.books.index'));

        $book = Book::firstOrFail();
        $this->assertSame(['Common', 'Elvish'], $book->languages);
        $this->assertSame(120, $book->published_year);
        $this->assertSame('Verin Ashdale', $book->author->title);
        $this->assertSame('people', $book->author->category);
        $this->assertSame('author of [[The Lost Annals]]', $book->author->description);

        $this->actingAs($admin)->get(route('admin.books.index'))->assertOk()->assertSee('Verin Ashdale');
        $this->actingAs($admin)->get(route('admin.books.edit', $book))->assertOk();
    }

    public function test_book_with_existing_author_reuses_the_person(): void
    {
        $person = LoreEntry::factory()->create(['category' => 'people', 'title' => 'Verin Ashdale', 'description' => 'A scholar.']);

        $this->actingAs($this->admin())->post(route('admin.books.store'), [
            'title' => 'Another Book',
            'author_name' => 'verin ashdale',
        ])->assertRedirect();

        $this->assertSame($person->id, Book::firstOrFail()->author_id);
        $this->assertSame(1, LoreEntry::where('category', 'people')->count());
        $this->assertSame('A scholar.', $person->fresh()->description);
    }

    public function test_book_languages_can_be_empty_and_dates_validate(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.books.store'), ['title' => 'Quiet'])->assertRedirect();
        $this->assertSame([], Book::firstOrFail()->languages);

        $this->actingAs($admin)->post(route('admin.books.store'), ['title' => 'Bad', 'published_day' => 40])
            ->assertSessionHasErrors(['published_day', 'published_month']);
    }

    public function test_author_search_can_be_limited_to_people(): void
    {
        LoreEntry::factory()->create(['category' => 'people', 'title' => 'Mira Dawn']);
        LoreEntry::factory()->create(['category' => 'places', 'title' => 'Mirador']);

        $this->actingAs($this->admin())->getJson(route('admin.entries.search', ['q' => 'mir', 'category' => 'people']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Mira Dawn');
    }

    public function test_non_admins_are_blocked(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.books.index'))->assertForbidden();
    }
}
