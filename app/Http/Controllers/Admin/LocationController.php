<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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

    public function store(LocationRequest $request): RedirectResponse
    {
        $this->persist(new Location, $request);

        return redirect()->route('admin.locations.index')->with('status', 'Location created.');
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.form', [
            'location' => $location,
            'parentOptions' => $this->parentOptions($location),
        ]);
    }

    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        $this->persist($location, $request);

        return redirect()->route('admin.locations.index')->with('status', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->children()->exists()) {
            return back()->withErrors(['delete' => 'Move or delete this location\'s children first.']);
        }

        $this->deleteMap($location);
        $location->delete();

        return redirect()->route('admin.locations.index')->with('status', 'Location deleted.');
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
        $data = $request->safe()->only(['name', 'description', 'parent_id', 'pin_x', 'pin_y']);

        if ($request->hasFile('map')) {
            $this->deleteMap($location);
            $data['map_image_path'] = $request->file('map')->store('maps', 'public');
        } elseif ($request->boolean('remove_map')) {
            $this->deleteMap($location);
            $data['map_image_path'] = null;
        }

        $location->fill($data)->save();
    }

    private function deleteMap(Location $location): void
    {
        if ($location->hasMap()) {
            Storage::disk('public')->delete($location->map_image_path);
        }
    }
}
