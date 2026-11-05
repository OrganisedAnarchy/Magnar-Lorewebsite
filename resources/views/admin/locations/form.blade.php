@extends('admin.layout')

@section('title', $location->exists ? 'Edit location' : 'New location')

@section('content')
    <h1>{{ $location->exists ? 'Edit location' : 'New location' }}</h1>

    <div id="location-form-app" data-props="{{ json_encode([
        'action' => $location->exists ? route('admin.locations.update', $location) : route('admin.locations.store'),
        'method' => $location->exists ? 'PUT' : 'POST',
        'csrf' => csrf_token(),
        'draftKey' => 'draft_location_'.($location->exists ? $location->getKey() : 'new'),
        'initial' => [
            'name' => old('name', $location->name),
            'description' => old('description', $location->description),
            'parent_id' => old('parent_id', $location->parent_id),
            'pin_x' => old('pin_x', $location->pin_x),
            'pin_y' => old('pin_y', $location->pin_y),
            'remove_map' => (bool) old('remove_map'),
            'summary' => old('summary', $location->summary),
            'celestial_kind' => old('celestial_kind', $location->celestial_kind),
        ],
        'isCelestialBody' => $location->isCelestialBody(),
        'celestialKinds' => \App\Models\Location::CELESTIAL_KINDS,
        'currentMapUrl' => $location->mapUrl(),
        'hasChildren' => $location->exists && $location->children()->exists(),
        'parentOptions' => $parentOptions,
        'errors' => $errors->getMessages(),
        'cancelUrl' => route('admin.locations.index'),
    ]) }}"></div>
@endsection
