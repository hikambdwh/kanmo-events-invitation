<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()
            ->withCount([
                'invitations',

                'invitations as checked_in_count' => function ($query) {
                    $query->whereNotNull('checked_in_at');
                },
            ])
            ->latest('event_date')
            ->paginate(10);

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['name']
        );

        $event = Event::create($validated);

        return redirect()
            ->route('admin.events.show', $event)
            ->with(
                'success',
                'Event berhasil dibuat.'
            );
    }

    public function show(Event $event): View
    {
        $event->loadCount([
            'invitations',

            'invitations as checked_in_count' => function ($query) {
                $query->whereNotNull('checked_in_at');
            },
        ]);

        $event->load([
            'invitations' => function ($query) {
                $query
                    ->latest()
                    ->limit(10);
            },
        ]);

        $statistics = [

            /*
            * Total kapasitas invitation.
            *
            * Contoh:
            *
            * QR A limit 1
            * QR B limit 5
            * QR C limit 3
            *
            * Total Invitations = 9
            */
            'total_invitations' => (int) $event
                ->invitations()
                ->sum('scan_limit'),


            /*
            * Jumlah QR fisik / invitation record.
            */
            'total_qr' => $event
                ->invitations()
                ->count(),


            /*
            * Total invitation yang sudah digunakan.
            *
            * QR dengan:
            *
            * limit = 5
            * scan_count = 2
            *
            * berarti menyumbang 2 checked in.
            */
            'checked_in' => (int) $event
                ->invitations()
                ->sum('scan_count'),


            /*
            * Jumlah QR yang masih dapat digunakan.
            *
            * Tidak peduli tersisa 1 atau 10 scan,
            * selama scan_count < scan_limit,
            * QR masih dianggap available.
            */
            'available_qr' => $event
                ->invitations()
                ->whereColumn(
                    'scan_count',
                    '<',
                    'scan_limit'
                )
                ->count(),
        ];

        return view(
            'admin.events.show',
            compact('event', 'statistics')
        );
    }

    public function edit(Event $event): View
    {
        return view(
            'admin.events.edit',
            compact('event')
        );
    }

    public function update(
        EventRequest $request,
        Event $event
    ): RedirectResponse {
        $validated = $request->validated();

        if ($event->name !== $validated['name']) {
            $validated['slug'] = $this->generateUniqueSlug(
                $validated['name'],
                $event->id
            );
        }

        $event->update($validated);

        return redirect()
            ->route('admin.events.show', $event)
            ->with(
                'success',
                'Event berhasil diperbarui.'
            );
    }

    public function destroy(
        Event $event
    ): RedirectResponse {
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with(
                'success',
                'Event berhasil dihapus.'
            );
    }

    private function generateUniqueSlug(
        string $name,
        ?int $ignoreId = null
    ): string {
        $baseSlug = Str::slug($name);

        $slug = $baseSlug;
        $counter = 1;

        while (
            Event::query()
            ->where('slug', $slug)
            ->when(
                $ignoreId,
                fn($query) =>
                $query->where('id', '!=', $ignoreId)
            )
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
