<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CelestialBodyController extends Controller
{
    public function show(string $body): View
    {
        $celestialBodies = config('celestial-bodies', []);
        $celestialBody = $celestialBodies[$body] ?? null;

        abort_unless(is_array($celestialBody), 404);

        return view('celestial-bodies.show', ['body' => $celestialBody]);
    }
}
