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


                    /*
                     * Lock row supaya dua HP staff
                     * yang scan bersamaan tidak dapat
                     * melewati scan limit.
                     */
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
                     * Kalau guest code ditemukan
                     * di lebih dari satu event,
                     * jangan check-in otomatis.
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
                 * VALIDATE SCAN LIMIT
                 * =========================================
                 */

                $scanLimit =
                    (int) $invitation->scan_limit;

                $scanCount =
                    (int) $invitation->scan_count;


                /*
                 * Safety kalau data lama / invalid
                 * memiliki limit 0.
                 */
                if ($scanLimit < 1) {

                    return response()->json([
                        'status' => 'invalid',

                        'message' =>
                        'Scan limit invitation tidak valid.',
                    ], 422);
                }


                /*
                 * =========================================
                 * LIMIT SUDAH HABIS
                 * =========================================
                 */

                if (
                    $scanCount >=
                    $scanLimit
                ) {

                    return response()->json([

                        'status' =>
                        'limit_reached',

                        'message' =>
                        'QR sudah mencapai batas maksimal scan.',

                        'guest_code' =>
                        $invitation->guest_code,

                        'event' =>
                        $event->name,

                        'scan_count' =>
                        $scanCount,

                        'scan_limit' =>
                        $scanLimit,

                        'remaining_scans' =>
                        0,

                        'checked_in_at' =>
                        $invitation
                            ->checked_in_at
                            ?->format(
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

                $newScanCount =
                    $scanCount + 1;


                $remainingScans =
                    max(
                        $scanLimit
                            - $newScanCount,
                        0
                    );


                $invitation->update([

                    'scan_count' =>
                    $newScanCount,

                    /*
                     * Sekarang checked_in_at berarti:
                     *
                     * waktu scan terakhir.
                     */
                    'checked_in_at' =>
                    now(),

                    /*
                     * checked_in_by berarti:
                     *
                     * staff/admin terakhir yang scan.
                     */
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

                    'scan_count' =>
                    $newScanCount,

                    'scan_limit' =>
                    $scanLimit,

                    'remaining_scans' =>
                    $remainingScans,

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
