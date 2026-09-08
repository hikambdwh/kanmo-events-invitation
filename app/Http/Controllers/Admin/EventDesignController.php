<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDesign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EventDesignController extends Controller
{
    public function edit(
        Event $event
    ): View {
        $design = EventDesign::firstOrCreate(
            [
                'event_id' => $event->id,
            ],
            [
                'design_data' => $this->defaultDesign(),
            ]
        );

        $previewInvitation = $event
            ->invitations()
            ->orderBy('guest_number')
            ->first();

        $previewValue = $previewInvitation
            ? $event->qr_prefix . ':' . $previewInvitation->qr_token
            : $event->qr_prefix . ':PREVIEW';

        return view(
            'admin.designs.edit',
            compact(
                'event',
                'design',
                'previewInvitation',
                'previewValue'
            )
        );
    }


    public function uploadBackground(
        Request $request,
        Event $event
    ): RedirectResponse {
        $validated = $request->validate([
            'background' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        $file = $validated['background'];

        $imageInfo = getimagesize(
            $file->getRealPath()
        );

        if (!$imageInfo) {
            return back()->withErrors([
                'background' =>
                'File image tidak valid.',
            ]);
        }

        [$width, $height] = $imageInfo;

        $design = EventDesign::firstOrCreate(
            [
                'event_id' => $event->id,
            ],
            [
                'design_data' => $this->defaultDesign(),
            ]
        );

        /*
         * Hapus background lama.
         */
        if ($design->background_path) {
            Storage::disk('public')
                ->delete(
                    $design->background_path
                );
        }

        $path = $file->store(
            'event-designs/' . $event->id,
            'public'
        );

        $design->update([
            'background_path' => $path,
            'canvas_width' => $width,
            'canvas_height' => $height,
        ]);

        return redirect()
            ->route(
                'admin.events.design.edit',
                $event
            )
            ->with(
                'success',
                'Invitation background berhasil diupload.'
            );
    }


    public function update(
        Request $request,
        Event $event
    ): RedirectResponse {
        $validated = $request->validate([
            'qr_x' => [
                'required',
                'numeric',
                'between:0,1',
            ],

            'qr_y' => [
                'required',
                'numeric',
                'between:0,1',
            ],

            'qr_size' => [
                'required',
                'numeric',
                'between:0.05,0.5',
            ],

            'qr_foreground' => [
                'required',
                'regex:/^[0-9A-Fa-f]{6}$/',
            ],

            'qr_background' => [
                'required',
                'regex:/^[0-9A-Fa-f]{6}$/',
            ],

            'qr_margin' => [
                'required',
                'integer',
                'between:0,50',
            ],
        ]);

        $design = EventDesign::firstOrCreate(
            [
                'event_id' => $event->id,
            ]
        );

        $design->update([
            'design_data' => [
                'qr' => [
                    'x' => (float) $validated['qr_x'],
                    'y' => (float) $validated['qr_y'],

                    'size' =>
                    (float) $validated['qr_size'],

                    'foreground' =>
                    strtoupper(
                        $validated['qr_foreground']
                    ),

                    'background' =>
                    strtoupper(
                        $validated['qr_background']
                    ),

                    'margin' =>
                    (int) $validated['qr_margin'],
                ],
            ],
        ]);

        return back()->with(
            'success',
            'Design berhasil disimpan.'
        );
    }


    private function defaultDesign(): array
    {
        return [
            'qr' => [
                'x' => 0.70,
                'y' => 0.70,
                'size' => 0.20,

                'foreground' =>
                '000000',

                'background' =>
                'FFFFFF',

                'margin' =>
                10,
            ],
        ];
    }
}
