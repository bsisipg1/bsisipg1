<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->where('is_active', true)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Event $event) => $event->toApiArray());

        return response()->json([
            'data' => $events,
        ]);
    }

    public function show(Event $event): JsonResponse
    {
        if (! $event->is_active) {
            abort(404);
        }

        return response()->json([
            'data' => $event->toApiArray(),
        ]);
    }
}
