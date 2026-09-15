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
use Illuminate\Http\JsonResponse;


class InvitationController extends Controller
{
    public function index(
        Request $request,
        Event $event
    ): View|JsonResponse {

        $search = trim(
            $request->get('search', '')
        );

        $status = $request->get(
            'status',
            ''
        );


        $invitations = $event
            ->invitations()

            ->when(
                $search !== '',
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
                $query->whereColumn(
                    'scan_count',
                    '<',
                    'scan_limit'
                )
            )

            ->when(
                $status === 'checked_in',
                fn($query) =>
                $query->whereColumn(
                    'scan_count',
                    '>=',
                    'scan_limit'
                )
            )

            ->orderBy('guest_number')

            ->paginate(20)

            ->withQueryString();


        /*
     * =========================================
     * AJAX REQUEST
     * =========================================
     */
        if ($request->expectsJson()) {

            return response()->json([
                'html' => view(
                    'admin.invitations._table',
                    [
                        'event' => $event,
                        'invitations' => $invitations,
                    ]
                )->render(),
            ]);
        }


        /*
     * =========================================
     * NORMAL PAGE LOAD
     * =========================================
     */

        $statistics = [

            'total' => $event
                ->invitations()
                ->count(),

            'available' => $event
                ->invitations()
                ->whereColumn(
                    'scan_count',
                    '<',
                    'scan_limit'
                )
                ->count(),

            'checked_in' => $event
                ->invitations()
                ->whereColumn(
                    'scan_count',
                    '>=',
                    'scan_limit'
                )
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

                    'scan_limit' => 1,
                    'scan_count' => 0,

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
                ' invitations have been successfully generated.'
            );
    }

    public function markAsScanned(
        Event $event,
        Invitation $invitation
    ): RedirectResponse {

        abort_unless(
            $invitation->event_id === $event->id,
            404
        );


        $wasScanned = DB::transaction(
            function () use (
                $invitation
            ) {

                $lockedInvitation =
                    Invitation::query()
                    ->whereKey(
                        $invitation->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
             * Sudah tidak punya jatah scan.
             */
                if (
                    $lockedInvitation->scan_count
                    >=
                    $lockedInvitation->scan_limit
                ) {
                    return false;
                }


                /*
             * Consume 1 scan.
             */
                $lockedInvitation->update([

                    'scan_count' =>
                    $lockedInvitation
                        ->scan_count + 1,

                    'checked_in_at' =>
                    now(),

                    'checked_in_by' =>
                    auth()->id(),

                ]);


                return true;
            }
        );


        if (!$wasScanned) {

            return back()->with(
                'info',
                $invitation->guest_code
                    . ' sudah mencapai scan limit.'
            );
        }


        $invitation->refresh();


        return back()->with(
            'success',
            $invitation->guest_code
                . ' has been manually scanned once. '
                . $invitation->remaining_scans
                . ' scan(s) remaining.'
        );
    }


    public function reset(
        Event $event,
        Invitation $invitation
    ): RedirectResponse {

        abort_unless(
            $invitation->event_id === $event->id,
            404
        );


        if ($invitation->scan_count === 0) {
            return back()->with(
                'info',
                $invitation->guest_code
                    . ' belum pernah digunakan.'
            );
        }


        $invitation->update([
            'scan_count' => 0,

            'checked_in_at' => null,

            'checked_in_by' => null,
        ]);


        return back()->with(
            'success',
            $invitation->guest_code
                . ' has been reset and can be used again.'
        );
    }

    public function setLimit(
        Request $request,
        Event $event,
        Invitation $invitation
    ): RedirectResponse {

        abort_unless(
            $invitation->event_id === $event->id,
            404
        );


        $validated = $request->validate([
            'scan_limit' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ]);


        $newLimit =
            (int) $validated['scan_limit'];


        /*
     * Jangan izinkan limit lebih kecil
     * daripada jumlah yang sudah digunakan.
     *
     * Contoh:
     * used = 3
     * limit baru = 2
     *
     * Tidak masuk akal.
     */
        if (
            $newLimit <
            $invitation->scan_count
        ) {
            return back()->withErrors([
                'scan_limit' =>
                'Limit cannot be lower than the number of scans already used.',
            ]);
        }


        $invitation->update([
            'scan_limit' => $newLimit,
        ]);


        return back()->with(
            'success',
            $invitation->guest_code
                . ' scan limit has been updated to '
                . $newLimit
                . '.'
        );
    }
}
