<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $trips = $request->user()
            ->trips()
            ->withCount('locations')
            ->get()
            ->map(fn (Trip $trip) => $trip->toApiArray());

        return response()->json([
            'data' => $trips,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'distinct', 'exists:locations,id'],
        ]);

        $trip = $request->user()->trips()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        $this->syncTripLocations($trip, $validated['location_ids'] ?? []);

        return response()->json([
            'data' => $this->formatTrip($trip),
        ], 201);
    }

    public function show(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureOwnedByUser($request, $trip);

        return response()->json([
            'data' => $this->formatTrip($trip),
        ]);
    }

    public function update(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureOwnedByUser($request, $trip);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location_ids' => ['sometimes', 'array'],
            'location_ids.*' => ['integer', 'distinct', 'exists:locations,id'],
        ]);

        $trip->update(collect($validated)->only([
            'name',
            'description',
            'start_date',
            'end_date',
        ])->all());

        if (array_key_exists('location_ids', $validated)) {
            $this->syncTripLocations($trip, $validated['location_ids']);
        }

        return response()->json([
            'data' => $this->formatTrip($trip),
        ]);
    }

    public function destroy(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureOwnedByUser($request, $trip);

        $trip->delete();

        return response()->json([
            'message' => 'Trip deleted.',
        ]);
    }

    public function syncLocations(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureOwnedByUser($request, $trip);

        $validated = $request->validate([
            'location_ids' => ['present', 'array'],
            'location_ids.*' => ['integer', 'distinct', 'exists:locations,id'],
        ]);

        $this->syncTripLocations($trip, $validated['location_ids']);

        return response()->json([
            'data' => $this->formatTrip($trip),
        ]);
    }

    public function removeLocation(Request $request, Trip $trip, Location $location): JsonResponse
    {
        $this->ensureOwnedByUser($request, $trip);

        $detached = $trip->locations()->detach($location->id);

        if ($detached === 0) {
            abort(404, 'Location is not part of this trip.');
        }

        return response()->json([
            'data' => $this->formatTrip($trip),
        ]);
    }

    private function ensureOwnedByUser(Request $request, Trip $trip): void
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(404);
        }
    }

    /**
     * @param  list<int>  $locationIds
     */
    private function syncTripLocations(Trip $trip, array $locationIds): void
    {
        $payload = [];

        foreach (array_values($locationIds) as $index => $locationId) {
            $payload[$locationId] = ['sort_order' => $index + 1];
        }

        $trip->locations()->sync($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTrip(Trip $trip): array
    {
        $trip->load([
            'locations' => fn ($query) => $query
                ->with('gallery')
                ->withCount('ratings')
                ->withAvg('ratings', 'rating'),
        ]);
        $trip->loadCount('locations');

        return $trip->toApiArray();
    }
}
