<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Language;
use App\Models\Location;
use App\Models\LoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoreImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_admin_can_upload_an_image_for_the_markdown_editor(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('admin.images.store'), ['image' => UploadedFile::fake()->image('banner.png')])
            ->assertOk();

        $files = Storage::disk('public')->allFiles('uploads');
        $this->assertCount(1, $files);
        $this->assertSame(Storage::disk('public')->url($files[0]), $response->json('url'));
    }

    public function test_editor_uploads_are_stored_on_the_images_disk(): void
    {
        config(['filesystems.images_disk' => 's3']);
        Storage::fake('s3');

        $this->actingAs($this->admin())
            ->postJson(route('admin.images.store'), ['image' => UploadedFile::fake()->image('banner.png')])
            ->assertOk();

        $this->assertCount(1, Storage::disk('s3')->allFiles('uploads'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_editor_uploads_must_be_images_and_need_an_admin(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.images.store'), ['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.images.store'), ['image' => UploadedFile::fake()->image('banner.png')])
            ->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_entry_image_can_be_uploaded_replaced_and_removed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.entries.store'), [
            'category' => 'people',
            'title' => 'Mira Dawn',
            'image' => UploadedFile::fake()->image('first.png'),
        ])->assertSessionHasNoErrors();

        $entry = LoreEntry::firstOrFail();
        $first = $entry->image_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($admin)->put(route('admin.entries.update', $entry), [
            'category' => 'people',
            'title' => 'Mira Dawn',
            'image' => UploadedFile::fake()->image('second.png'),
        ])->assertSessionHasNoErrors();

        $second = $entry->refresh()->image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($admin)->put(route('admin.entries.update', $entry), [
            'category' => 'people',
            'title' => 'Mira Dawn',
        ])->assertSessionHasNoErrors();
        $this->assertSame($second, $entry->refresh()->image_path);

        $this->actingAs($admin)->put(route('admin.entries.update', $entry), [
            'category' => 'people',
            'title' => 'Mira Dawn',
            'remove_image' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull($entry->refresh()->image_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_entry_image_is_deleted_with_the_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.entries.store'), [
            'category' => 'organisations',
            'title' => 'The Guild',
            'image' => UploadedFile::fake()->image('emblem.png'),
        ]);
        $entry = LoreEntry::firstOrFail();

        $this->actingAs($admin)->delete(route('admin.entries.destroy', $entry));

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_entry_image_must_be_an_image(): void
    {
        $this->actingAs($this->admin())->post(route('admin.entries.store'), [
            'category' => 'people',
            'title' => 'Mira Dawn',
            'image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, LoreEntry::count());
    }

    public function test_image_label_depends_on_the_kind_of_lore(): void
    {
        $this->assertSame('Portrait', (new LoreEntry(['category' => 'people']))->imageLabel());
        $this->assertSame('Emblem', (new LoreEntry(['category' => 'organisations']))->imageLabel());
        $this->assertSame('Image', (new LoreEntry)->imageLabel());
        $this->assertSame('Cover', (new Book)->imageLabel());
    }

    public function test_entry_form_receives_the_image_labels_and_current_image(): void
    {
        $entry = LoreEntry::factory()->create(['category' => 'people', 'image_path' => 'lore/lore_entries/mira.png']);

        $this->actingAs($this->admin())
            ->get(route('admin.entries.edit', $entry))
            ->assertOk()
            ->assertSee('Portrait')
            ->assertSee('Emblem')
            ->assertSee('mira.png');
    }

    public function test_locations_languages_and_books_accept_an_image(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.locations.store'), [
            'name' => 'Lomile',
            'image' => UploadedFile::fake()->image('lomile.png'),
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.languages.store'), [
            'name' => 'Old Tongue',
            'image' => UploadedFile::fake()->image('script.png'),
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'The Lost Annals',
            'image' => UploadedFile::fake()->image('cover.png'),
        ])->assertSessionHasNoErrors();

        foreach ([Location::firstOrFail(), Language::firstOrFail(), Book::firstOrFail()] as $record) {
            Storage::disk('public')->assertExists($record->image_path);
        }

        $this->actingAs($admin)->put(route('admin.books.update', Book::firstOrFail()), [
            'title' => 'The Lost Annals',
            'remove_image' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertNull(Book::firstOrFail()->image_path);
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }
}
