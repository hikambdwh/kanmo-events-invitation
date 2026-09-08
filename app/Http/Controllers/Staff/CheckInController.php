<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function store(
        Request $request
    ): JsonResponse {

        $validated = $request->validate([
            'qr_value' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $input = trim(
            $validated['qr_value']
        );

        return DB::transaction(
            function () use (
                $input,
                $request
            ) {

                $event = null;
                $invitation = null;

                /*
                 * =========================================
                 * MODE 1
                 * QR SCANNER
                 *
                 * Format:
                 * RogerVivier:TOKEN
                 * =========================================
                 */
                if (str_contains($input, ':')) {

                    [
                        $eventPrefix,
                        $token
                    ] = explode(
                        ':',
                        $input,
                        2
                    );

                    $eventPrefix =
                        trim($eventPrefix);

                    $token =
                        trim($token);

                    if (
                        $eventPrefix === ''
                        || $token === ''
                    ) {
                        return response()->json([
                            'status' => 'invalid',
                            'message' =>
                            'Format QR tidak valid.',
                        ], 422);
                    }


                    $event = Event::query()
                        ->where(
                            'qr_prefix',
                            $eventPrefix
                        )
                        ->first();


                    if (!$event) {
                        return response()->json([
                            'status' => 'invalid',
                            'message' =>
                            'Event QR tidak ditemukan.',
                        ], 404);
                    }


                    $invitation =
                        Invitation::query()
                        ->where(
                            'event_id',
                            $event->id
                        )
                        ->where(
                            'qr_token',
                            $token
                        )
                        ->lockForUpdate()
                        ->first();


                    if (!$invitation) {
                        return response()->json([
                            'status' => 'invalid',
                            'message' =>
                            'QR tidak terdaftar.',
                        ], 404);
                    }
                }

                /*
                 * =========================================
                 * MODE 2
                 * MANUAL GUEST CODE
                 *
                 * Contoh:
                 * guest00001
                 * =========================================
                 */ else {

                    $guestCode = $input;


                    /*
                     * Ambil maksimal 2 untuk memastikan
                     * guest_code tidak duplicate.
                     */
                    $invitations =
                        Invitation::query()
                        ->where(
                            'guest_code',
                            $guestCode
                        )
                        ->limit(2)
                        ->lockForUpdate()
                        ->get();


                    if ($invitations->isEmpty()) {

                        return response()->json([
                            'status' => 'invalid',

                            'message' =>
                            'Guest code tidak ditemukan.',
                        ], 404);
                    }


                    /*
                     * Guest code sebaiknya unique.
                     *
                     * Kalau ternyata terdapat kode yang
                     * sama pada lebih dari satu invitation,
                     * jangan melakukan check-in otomatis.
                     */
                    if ($invitations->count() > 1) {

                        return response()->json([
                            'status' => 'invalid',

                            'message' =>
                            'Guest code ditemukan pada lebih dari satu invitation.',
                        ], 422);
                    }


                    $invitation =
                        $invitations->first();


                    $event = Event::query()
                        ->find(
                            $invitation->event_id
                        );


                    if (!$event) {

                        return response()->json([
                            'status' => 'invalid',

                            'message' =>
                            'Event invitation tidak ditemukan.',
                        ], 404);
                    }
                }


                /*
                 * =========================================
                 * CHECK ALREADY CHECKED IN
                 * =========================================
                 */
                if ($invitation->checked_in_at) {

                    return response()->json([
                        'status' =>
                        'already_checked_in',

                        'message' =>
                        'Guest sudah check-in.',

                        'guest_code' =>
                        $invitation->guest_code,

                        'event' =>
                        $event->name,

                        'checked_in_at' =>
                        $invitation
                            ->checked_in_at
                            ->format(
                                'd M Y H:i:s'
                            ),

                        'checked_in_by' =>
                        $invitation
                            ->checkedInBy
                            ?->name,

                    ], 409);
                }


                /*
                 * =========================================
                 * CHECK-IN
                 * =========================================
                 */
                $invitation->update([

                    'checked_in_at' =>
                    now(),

                    'checked_in_by' =>
                    $request->user()->id,

                ]);


                return response()->json([

                    'status' =>
                    'success',

                    'message' =>
                    'Check-in berhasil.',

                    'guest_code' =>
                    $invitation->guest_code,

                    'event' =>
                    $event->name,

                    'checked_in_at' =>
                    $invitation
                        ->checked_in_at
                        ->format(
                            'd M Y H:i:s'
                        ),

                ]);
            }
        );
    }
}
