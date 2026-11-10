<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('admin.locations.index', [
            'locations' => Location::query()->with('parent')->withCount('children')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.locations.form', [
            'location' => new Location,
            'parentOptions' => $this->parentOptions(null),
        ]);
    }

    public function store(LocationRequest $request): JsonResponse|RedirectResponse
    {
        $location = new Location;
        $this->persist($location, $request);

        return $this->respond($request, redirect()->route('admin.locations.index'), 'Location created.', $this->savedPayload($location));
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.form', [
            'location' => $location,
            'parentOptions' => $this->parentOptions($location),
        ]);
    }

    public function update(LocationRequest $request, Location $location): JsonResponse|RedirectResponse
    {
        $this->persist($location, $request);

        return $this->respond($request, redirect()->route('admin.locations.index'), 'Location updated.', $this->savedPayload($location));
    }

    public function destroy(Request $request, Location $location): JsonResponse|RedirectResponse
    {
        if ($location->isChartBody()) {
            $message = 'Celestial bodies are part of the system chart and cannot be deleted.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['delete' => $message]);
        }

        if ($location->children()->exists()) {
            $message = 'Move or delete this location\'s children first.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['delete' => $message]);
        }

        $this->deleteMap($location);
        $location->delete();

        return $this->respond($request, redirect()->route('admin.locations.index'), 'Location deleted.');
    }

    /**
     * @return array{id: int, action: string, method: string, edit_url: string, map_url: ?string}
     */
    private function savedPayload(Location $location): array
    {
        return [
            'id' => $location->id,
            'action' => route('admin.locations.update', $location),
            'method' => 'PUT',
            'edit_url' => route('admin.locations.edit', $location),
            'map_url' => $location->mapUrl(),
        ];
    }

    /**
     * Locations that may become this one's parent: those with a map, excluding itself and its descendants.
     *
     * @return list<array{id: int, name: string, map_url: ?string}>
     */
    private function parentOptions(?Location $location): array
    {
        $excluded = $location?->exists ? [$location->id, ...$location->descendantIds()] : [];

        return Location::query()
            ->whereNotNull('map_image_path')
            ->whereNotIn('id', $excluded)
            ->orderBy('name')
            ->get()
            ->map(fn (Location $option): array => ['id' => $option->id, 'name' => $option->name, 'map_url' => $option->mapUrl()])
            ->all();
    }

    private function persist(Location $location, LocationRequest $request): void
    {
        $data = $request->safe()->only(['name', 'description', 'is_celestial_body', 'parent_id', 'pin_x', 'pin_y']);

        if ($request->boolean('is_celestial_body')) {
            $data = [
                ...$data,
                ...$request->safe()->only(['summary', 'celestial_kind']),
                'location_type' => null,
                'building_type' => null,
                'settlement_type' => null,
                'parent_id' => null,
                'pin_x' => null,
                'pin_y' => null,
            ];
        } else {
            $data = [
                ...$data,
                ...$request->safe()->only(['location_type', 'building_type', 'settlement_type']),
                'summary' => null,
                'celestial_kind' => null,
            ];
        }

        if ($request->hasFile('map')) {
            $this->deleteMap($location);
            $data['map_image_path'] = $request->file('map')->store('maps', config('filesystems.images_disk'));
        } elseif ($request->boolean('remove_map')) {
            $this->deleteMap($location);
            $data['map_image_path'] = null;
        }

        $location->fill($data)->save();
    }

    private function deleteMap(Location $location): void
    {
        if ($location->hasMap()) {
            Location::imageDisk()->delete($location->map_image_path);
        }
    }
}
