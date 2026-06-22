<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locations = $request->user()
            ->savedLocations()
            ->with('gallery')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->get()
            ->map(fn (Location $location) => $location->toSavedApiArray());

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $user = $request->user();
        $user->savedLocations()->syncWithoutDetaching([$validated['location_id']]);

        $location = $user->savedLocations()
            ->with('gallery')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->whereKey($validated['location_id'])
            ->firstOrFail();

        return response()->json([
            'data' => $location->toSavedApiArray(),
        ], 201);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_ids' => ['present', 'array'],
            'location_ids.*' => ['integer', 'distinct', 'exists:locations,id'],
        ]);

        $request->user()->savedLocations()->sync($validated['location_ids']);

        $locations = $request->user()
            ->savedLocations()
            ->with('gallery')
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->get()
            ->map(fn (Location $location) => $location->toSavedApiArray());

        return response()->json([
            'data' => $locations,
        ]);
    }

    public function destroy(Request $request, Location $location): JsonResponse
    {
        $detached = $request->user()->savedLocations()->detach($location->id);

        if ($detached === 0) {
            abort(404, 'Location is not in your saved list.');
        }

        return response()->json([
            'message' => 'Location removed.',
        ]);
    }
}
