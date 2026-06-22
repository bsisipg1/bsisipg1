<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->toAppApiArray(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'saved' => $user->savedLocations()->count(),
                'visited' => $user->locationRatings()->distinct('location_id')->count('location_id'),
                'trips' => $user->trips()->count(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo !== null) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $request->file('profile_photo')->store(
                'users/profile-photos',
                'public',
            );
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->toAppApiArray(),
        ]);
    }

    public function updateHomeLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'home_location' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->home_location = $validated['home_location'] ?? null;
        $user->save();

        return response()->json([
            'message' => 'Home location updated.',
            'user' => $user->toAppApiArray(),
        ]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_photo === null) {
            abort(404, 'Profile photo not found.');
        }

        Storage::disk('public')->delete($user->profile_photo);

        $user->update(['profile_photo' => null]);

        return response()->json([
            'message' => 'Profile photo removed.',
            'user' => $user->toAppApiArray(),
        ]);
    }
}
