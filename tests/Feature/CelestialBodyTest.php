<?php

namespace Tests\Feature;

use Tests\TestCase;

class CelestialBodyTest extends TestCase
{
    public function test_the_welcome_page_provides_celestial_lore_to_the_interactive_system(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-celestial-bodies=', false)
            ->assertSee('Aegis')
            ->assertSee('Helia')
            ->assertSee('Ogunja')
            ->assertSee('Amial')
            ->assertSee('Hanar')
            ->assertSee('Misana')
            ->assertSee('Lysus');
    }

    public function test_each_celestial_body_page_shows_its_lore_and_a_return_link(): void
    {
        foreach (config('celestial-bodies') as $body) {
            $this->get(route('celestial-bodies.show', $body['slug']))
                ->assertOk()
                ->assertSee("{$body['name']} · The World of Magnar")
                ->assertSee($body['name'])
                ->assertSee($body['summary'])
                ->assertSee('Return to the system');
        }
    }

    public function test_helia_page_explains_the_seasonal_cycle_and_magnar_calendar(): void
    {
        $this->get(route('celestial-bodies.show', 'helia'))
            ->assertOk()
            ->assertSee('780 days')
            ->assertSee('two 390-day cycles')
            ->assertSee('13 months of 30 days')
            ->assertSee('195 days')
            ->assertSee('Ogunja, Amial, Hanar, and Misana');
    }

    public function test_unknown_celestial_body_pages_return_not_found(): void
    {
        $this->get(route('celestial-bodies.show', 'unknown-body'))
            ->assertNotFound();

        $this->get(route('celestial-bodies.show', 'magnar.name'))
            ->assertNotFound();
    }
}
