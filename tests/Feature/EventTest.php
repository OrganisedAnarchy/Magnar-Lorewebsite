<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\LoreEntry;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_can_be_created_and_associated_with_a_place_subjects_and_tags(): void
    {
        $place = Place::create([
            'name' => 'Lomile',
            'category' => 'populated_area',
        ]);
        $location = LoreEntry::create([
            'category' => 'places',
            'title' => 'Lomile lore',
            'description' => 'A busy city.',
        ]);
        $organisation = LoreEntry::create([
            'category' => 'organisations',
            'title' => 'Lomile Guard',
            'description' => 'The city watch.',
        ]);
        $warTag = Tag::create(['name' => 'war']);
        $event = Event::create([
            'title' => 'The Siege of Lomile',
            'description' => 'The city endured a long siege.',
            'world_year' => 142,
            'cycle' => 2,
            'month' => 11,
            'day' => 8,
            'place_id' => $place->id,
        ]);
        $event->loreEntries()->attach([$location->id, $organisation->id]);
        $event->tags()->attach($warTag);
        $event->refresh();

        $this->assertSame('The Siege of Lomile', $event->title);
        $this->assertSame(142, $event->world_year);
        $this->assertSame(2, $event->cycle);
        $this->assertSame(11, $event->month);
        $this->assertSame(8, $event->day);
        $this->assertSame($place->id, $event->place->id);
        $this->assertSame(
            [$location->id, $organisation->id],
            $event->loreEntries->modelKeys(),
        );
        $this->assertSame($warTag->id, $event->tags->sole()->id);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'The Siege of Lomile',
            'world_year' => 142,
            'cycle' => 2,
            'month' => 11,
            'day' => 8,
            'place_id' => $place->id,
        ]);
    }

    public function test_event_can_exist_without_an_optional_place_subjects_or_tags(): void
    {
        $event = Event::create([
            'title' => 'An event without associations',
            'description' => 'This event is not linked to a place or subjects.',
            'world_year' => 1,
            'cycle' => 1,
            'month' => 1,
            'day' => 1,
        ]);

        $this->assertNull($event->place);
        $this->assertCount(0, $event->loreEntries);
        $this->assertCount(0, $event->tags);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'place_id' => null,
        ]);
    }
}
