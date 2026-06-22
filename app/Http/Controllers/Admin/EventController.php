<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventTone;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/events/Index', [
            'events' => Event::query()
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Event $event) => $this->formatEvent($event)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/events/Create', [
            'types' => EventType::options(),
            'tones' => EventTone::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateEvent($request);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('events', 'public');
        }

        Event::create([
            ...$validated,
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    public function show(Event $event): Response
    {
        return Inertia::render('admin/events/Show', [
            'event' => $this->formatEvent($event),
        ]);
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('admin/events/Edit', [
            'event' => $this->formatEvent($event),
            'types' => EventType::options(),
            'tones' => EventTone::options(),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $this->validateEvent($request, updating: true);

        if ($request->hasFile('image')) {
            if ($event->image !== null) {
                Storage::disk('public')->delete($event->image);
            }

            $validated['image'] = $request->file('image')->store('events', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');

        $event->update($validated);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->image !== null) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEvent(Event $event): array
    {
        $data = $event->toApiArray();

        $data['created_at'] = $event->created_at?->format('M j, Y');
        $data['updated_at'] = $event->updated_at?->format('M j, Y');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_column(EventType::options(), 'value'))],
            'description' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'time' => ['nullable', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'in:'.implode(',', array_column(EventTone::options(), 'value'))],
            'image' => [$updating ? 'nullable' : 'nullable', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        unset($validated['image']);

        return $validated;
    }
}
