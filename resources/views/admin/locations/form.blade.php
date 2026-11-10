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
            'is_celestial_body' => (bool) old('is_celestial_body', $location->isCelestialBody()),
            'location_type' => old('location_type', $location->location_type),
            'building_type' => old('building_type', $location->building_type),
            'settlement_type' => old('settlement_type', $location->settlement_type),
        ],
        'isChartBody' => $location->isChartBody(),
        'celestialKinds' => \App\Models\Location::CELESTIAL_KINDS,
        'locationTypes' => \App\Models\Location::LOCATION_TYPES,
        'buildingType' => \App\Models\Location::TYPE_BUILDING,
        'settlementType' => \App\Models\Location::TYPE_RELIGIOUS_SETTLEMENT,
        'currentMapUrl' => $location->mapUrl(),
        'currentImageUrl' => $location->imageUrl(),
        'imageLabel' => $location->imageLabel(),
        'hasChildren' => $location->exists && $location->children()->exists(),
        'parentOptions' => $parentOptions,
        'errors' => $errors->getMessages(),
        'cancelUrl' => route('admin.locations.index'),
    ]) }}"></div>
@endsection
