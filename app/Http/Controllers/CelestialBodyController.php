<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\View\View;

class CelestialBodyController extends Controller
{
    public function show(string $body): View
    {
        $celestialBody = Location::query()->celestialBodies()->where('celestial_slug', $body)->firstOrFail();

        return view('celestial-bodies.show', [
            'body' => $celestialBody,
            'locations' => $celestialBody->children,
        ]);
    }
}
