<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationMapController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'locations' => Location::query()->whereNull('parent_id')->orderBy('name')->get()
                ->map(fn (Location $location): array => $this->summary($location))
                ->all(),
        ]);
    }

    public function show(Location $location): JsonResponse
    {
        $location->load('children');

        return response()->json([
            'id' => $location->id,
            'name' => $location->name,
            'description' => $location->description,
            'map_url' => $location->mapUrl(),
            'parent_id' => $location->parent_id,
            'breadcrumbs' => collect($location->ancestors())
                ->map(fn (Location $ancestor): array => ['id' => $ancestor->id, 'name' => $ancestor->name])
                ->all(),
            'children' => $location->hasMap()
                ? $location->children->map(fn (Location $child): array => $this->summary($child) + [
                    'pin_x' => $child->pin_x,
                    'pin_y' => $child->pin_y,
                ])->all()
                : [],
        ]);
    }

    /**
     * @return array{id: int, name: string, description: ?string, has_map: bool}
     */
    private function summary(Location $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'description' => $location->description,
            'has_map' => $location->hasMap(),
        ];
    }
}
