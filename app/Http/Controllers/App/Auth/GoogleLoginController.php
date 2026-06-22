<?php

namespace App\Http\Controllers\App\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleLoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $clientId = config('services.google.client_id');

        if (! $clientId) {
            abort(503, 'Google Sign-In is not configured.');
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $validated['id_token'],
        ]);

        if (! $response->ok()) {
            throw ValidationException::withMessages([
                'id_token' => ['Invalid Google token.'],
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        if (($payload['aud'] ?? null) !== $clientId) {
            throw ValidationException::withMessages([
                'id_token' => ['Google token audience mismatch.'],
            ]);
        }

        $googleId = (string) ($payload['sub'] ?? '');
        $email = (string) ($payload['email'] ?? '');
        $name = (string) ($payload['name'] ?? strtok($email, '@'));

        if ($googleId === '' || $email === '') {
            throw ValidationException::withMessages([
                'id_token' => ['Google account is missing required profile data.'],
            ]);
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first();

        if ($user) {
            if ($user->role !== UserRole::AppUser) {
                throw ValidationException::withMessages([
                    'email' => ['This account is not authorized for the mobile app.'],
                ]);
            }

            if ($user->google_id === null) {
                $user->forceFill(['google_id' => $googleId])->save();
            }
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => null,
                'role' => UserRole::AppUser,
                'email_verified_at' => now(),
            ]);
        }

        $pictureUrl = isset($payload['picture']) ? (string) $payload['picture'] : null;
        $this->syncProfilePhotoFromGoogle($user, $pictureUrl);

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->toAppApiArray(),
        ]);
    }

    private function syncProfilePhotoFromGoogle(User $user, ?string $pictureUrl): void
    {
        if ($pictureUrl === null || $pictureUrl === '' || $user->profile_photo !== null) {
            return;
        }

        $response = Http::timeout(15)->get($pictureUrl);

        if (! $response->successful()) {
            return;
        }

        $contents = $response->body();

        if ($contents === '') {
            return;
        }

        $extension = match ($response->header('Content-Type')) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $path = 'users/profile-photos/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, $contents);

        $user->forceFill(['profile_photo' => $path])->save();
    }
}
