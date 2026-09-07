<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ): View {
        $search = trim($request->get('search', ''));

        $status = $request->get('status');

        $invitations = $event->invitations()
            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(
                        'guest_code',
                        'like',
                        '%' . $search . '%'
                    );
                }
            )
            ->when(
                $status === 'available',
                fn($query) =>
                $query->whereNull('checked_in_at')
            )
            ->when(
                $status === 'checked_in',
                fn($query) =>
                $query->whereNotNull('checked_in_at')
            )
            ->orderBy('guest_number')
            ->paginate(20)
            ->withQueryString();

        $statistics = [
            'total' => $event
                ->invitations()
                ->count(),

            'available' => $event
                ->invitations()
                ->whereNull('checked_in_at')
                ->count(),

            'checked_in' => $event
                ->invitations()
                ->whereNotNull('checked_in_at')
                ->count(),
        ];

        return view(
            'admin.invitations.index',
            compact(
                'event',
                'invitations',
                'statistics',
                'search',
                'status'
            )
        );
    }

    public function generate(
        Request $request,
        Event $event
    ): RedirectResponse {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ]);

        $quantity = $validated['quantity'];

        DB::transaction(function () use (
            $event,
            $quantity
        ) {
            $lockedEvent = Event::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lastGuestNumber = Invitation::query()
                ->where(
                    'event_id',
                    $lockedEvent->id
                )
                ->max('guest_number') ?? 0;

            $now = now();

            $invitations = [];

            for ($i = 1; $i <= $quantity; $i++) {
                $guestNumber =
                    $lastGuestNumber + $i;

                $invitations[] = [
                    'event_id' =>
                    $lockedEvent->id,

                    'guest_number' =>
                    $guestNumber,

                    'guest_code' =>
                    'guest' .
                        str_pad(
                            $guestNumber,
                            5,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'qr_token' =>
                    (string) Str::ulid(),

                    'checked_in_at' =>
                    null,

                    'checked_in_by' =>
                    null,

                    'created_at' =>
                    $now,

                    'updated_at' =>
                    $now,
                ];
            }

            Invitation::insert($invitations);
        });

        return redirect()
            ->route(
                'admin.events.invitations.index',
                $event
            )
            ->with(
                'success',
                $quantity .
                    ' invitation berhasil digenerate.'
            );
    }
}
