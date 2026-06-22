<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->latest()
                ->get()
                ->map(fn (User $user) => $user->toAdminApiArray()),
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        if (! $user->isAppUser()) {
            abort(403, 'Only app user accounts can be deleted.');
        }

        if ($user->profile_photo !== null) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', 'App user deleted successfully.');
    }
}
