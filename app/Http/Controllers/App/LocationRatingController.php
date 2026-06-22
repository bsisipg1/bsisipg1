<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\LocationRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationRatingController extends Controller
{
    public function index(Location $location): JsonResponse
    {
        $ratings = $location->ratings()
            ->with('user:id,name,profile_photo')
            ->latest()
            ->get()
            ->map(fn (LocationRating $rating) => $rating->toApiArray());

        return response()->json([
            'data' => $ratings,
        ]);
    }

    public function mine(Request $request, Location $location): JsonResponse
    {
        $rating = $location->ratings()
            ->with('user:id,name,profile_photo')
            ->where('user_id', $request->user()->id)
            ->first();

        if ($rating === null) {
            abort(404, 'You have not rated this location yet.');
        }

        return response()->json([
            'data' => $rating->toApiArray(),
        ]);
    }

    public function store(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $rating = LocationRating::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'location_id' => $location->id,
            ],
            [
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ],
        );

        $rating->load('user:id,name,profile_photo');

        return response()->json([
            'data' => $rating->toApiArray(),
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Location $location): JsonResponse
    {
        $deleted = LocationRating::query()
            ->where('user_id', $request->user()->id)
            ->where('location_id', $location->id)
            ->delete();

        if ($deleted === 0) {
            abort(404, 'You have not rated this location yet.');
        }

        return response()->json([
            'message' => 'Rating removed.',
        ]);
    }
}
