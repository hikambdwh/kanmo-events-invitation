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

        $qrValue = trim(
            $validated['qr_value']
        );

        /*
         * QR harus memiliki format:
         *
         * RogerVivier:TOKEN
         */
        if (!str_contains($qrValue, ':')) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Format QR tidak valid.',
            ], 422);
        }

        [$eventPrefix, $token] =
            explode(':', $qrValue, 2);

        $eventPrefix = trim($eventPrefix);
        $token = trim($token);

        if (
            $eventPrefix === ''
            || $token === ''
        ) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Format QR tidak valid.',
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
                'message' => 'Event QR tidak ditemukan.',
            ], 404);
        }

        return DB::transaction(
            function () use (
                $event,
                $token,
                $request
            ) {

                /*
                 * lockForUpdate penting jika QR
                 * hampir bersamaan discan oleh
                 * dua staff.
                 */
                $invitation = Invitation::query()
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

                if ($invitation->checked_in_at) {
                    return response()->json([
                        'status' =>
                        'already_checked_in',

                        'message' =>
                        'QR sudah check-in/discan.',

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

                $invitation->update([
                    'checked_in_at' =>
                    now(),

                    'checked_in_by' =>
                    $request->user()->id,
                ]);

                return response()->json([
                    'status' => 'success',

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
