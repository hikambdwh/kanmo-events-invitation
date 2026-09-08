<?php

namespace App\Http\Controllers\Admin;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Export;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvitationExportController extends Controller
{
    /**
     * Membuat export baru atau melanjutkan export
     * yang sebelumnya belum selesai.
     */
    public function start(Event $event): JsonResponse
    {
        $existingExport = Export::query()
            ->where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->where('type', 'qr_only')
            ->where('status', 'processing')
            ->latest()
            ->first();

        /*
         * Kalau sebelumnya browser ditutup di tengah proses,
         * kita lanjutkan export lama.
         */
        if ($existingExport) {
            return response()->json(
                $this->exportResponse(
                    $event,
                    $existingExport
                )
            );
        }

        $maxGuestNumber = $event
            ->invitations()
            ->max('guest_number');

        if (!$maxGuestNumber) {
            return response()->json([
                'message' =>
                'Belum ada invitation yang bisa diexport.',
            ], 422);
        }

        $total = $event
            ->invitations()
            ->where(
                'guest_number',
                '<=',
                $maxGuestNumber
            )
            ->count();

        $export = Export::create([
            'event_id' => $event->id,
            'user_id' => auth()->id(),

            'type' => 'qr_only',
            'status' => 'processing',

            'total_items' => $total,
            'processed_items' => 0,

            'max_guest_number' =>
            $maxGuestNumber,

            'last_guest_number' =>
            null,
        ]);

        $directory =
            'exports/' . $event->id;

        Storage::disk('local')
            ->makeDirectory($directory);

        $fileName =
            Str::slug($event->qr_prefix)
            . '-QR-'
            . $export->id
            . '.zip';

        $relativePath =
            $directory . '/' . $fileName;

        $absolutePath =
            Storage::disk('local')
            ->path($relativePath);

        /*
         * Buat ZIP kosong terlebih dahulu.
         */
        $zip = new ZipArchive();

        $result = $zip->open(
            $absolutePath,
            ZipArchive::CREATE
                | ZipArchive::OVERWRITE
        );

        if ($result !== true) {
            $export->delete();

            return response()->json([
                'message' =>
                'Tidak dapat membuat file ZIP.',
            ], 500);
        }

        $zip->close();

        $export->update([
            'file_name' => $fileName,
            'file_path' => $relativePath,
        ]);

        return response()->json(
            $this->exportResponse(
                $event,
                $export->fresh()
            )
        );
    }


    /**
     * Memproses satu batch QR.
     */
    public function process(
        Event $event,
        Export $export
    ): JsonResponse {
        $this->authorizeExport(
            $event,
            $export
        );

        if ($export->status === 'completed') {
            return response()->json(
                $this->exportResponse(
                    $event,
                    $export
                )
            );
        }

        /*
         * Mencegah dua tab/browser memproses batch
         * yang sama bersamaan.
         */
        $lock = Cache::lock(
            'qr-export-' . $export->id,
            120
        );

        if (!$lock->get()) {
            return response()->json([
                'message' =>
                'Export sedang diproses oleh request lain.',
            ], 409);
        }

        try {
            $export->refresh();

            if ($export->status === 'completed') {
                return response()->json(
                    $this->exportResponse(
                        $event,
                        $export
                    )
                );
            }

            /*
             * Shared hosting:
             * kita sengaja hanya proses 10 QR
             * per HTTP request.
             */
            $batchSize = 10;

            $invitations = $event
                ->invitations()
                ->where(
                    'guest_number',
                    '>',
                    $export->last_guest_number ?? 0
                )
                ->where(
                    'guest_number',
                    '<=',
                    $export->max_guest_number
                )
                ->orderBy('guest_number')
                ->limit($batchSize)
                ->get();

            /*
             * Tidak ada lagi invitation.
             */
            if ($invitations->isEmpty()) {
                $export->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                return response()->json(
                    $this->exportResponse(
                        $event,
                        $export->fresh()
                    )
                );
            }

            /*
             * Generate 10 QR secara parallel,
             * bukan satu per satu.
             */
            $responses = Http::pool(
                function (Pool $pool) use (
                    $invitations,
                    $event
                ) {
                    $requests = [];

                    foreach ($invitations as $invitation) {
                        $qrValue =
                            $event->qr_prefix
                            . ':'
                            . $invitation->qr_token;

                        $requests[] = $pool
                            ->as(
                                (string) $invitation->id
                            )
                            ->connectTimeout(5)
                            ->timeout(20)
                            ->get(
                                'https://api.qrserver.com/v1/create-qr-code/',
                                [
                                    'size' =>
                                    '1000x1000',

                                    'data' =>
                                    $qrValue,

                                    'format' =>
                                    'png',

                                    'margin' =>
                                    10,
                                ]
                            );
                    }

                    return $requests;
                }
            );

            /*
             * Pastikan semua request berhasil
             * sebelum menulis ke ZIP.
             */
            foreach ($invitations as $invitation) {
                $response =
                    $responses[(string) $invitation->id] ?? null;

                if (
                    !$response instanceof Response
                    || $response->failed()
                ) {
                    throw new RuntimeException(
                        'Gagal generate QR '
                            . $invitation->guest_code
                    );
                }
            }

            $absolutePath =
                Storage::disk('local')
                ->path(
                    $export->file_path
                );

            $zip = new ZipArchive();

            $result = $zip->open(
                $absolutePath,
                ZipArchive::CREATE
            );

            if ($result !== true) {
                throw new RuntimeException(
                    'Tidak dapat membuka ZIP.'
                );
            }

            $baseFolder = pathinfo(
                $export->file_name,
                PATHINFO_FILENAME
            );

            foreach ($invitations as $invitation) {

                $imagePath =
                    $baseFolder
                    . '/images/'
                    . $invitation->guest_code
                    . '.png';

                /*
                * Jika file sudah pernah masuk ZIP,
                * tidak perlu ditambahkan lagi.
                */
                if ($zip->locateName($imagePath) !== false) {
                    continue;
                }

                $response =
                    $responses[(string) $invitation->id];

                /*
                * QR ONLY:
                * langsung masukkan PNG dari QR Server.
                */
                $zip->addFromString(
                    $imagePath,
                    $response->body()
                );
            }

            $processed =
                $export->processed_items
                + $invitations->count();

            $completed =
                $processed
                >= $export->total_items;


            if ($completed) {

                $excelBinary =
                    $this->generateExportExcel(
                        $event,
                        $export
                    );

                $zip->addFromString(
                    $baseFolder
                        . '/invitation-data.xlsx',
                    $excelBinary
                );
            }
            

            $zip->close();


            $lastGuest =
                $invitations
                ->max('guest_number');


            $export->update([
                'processed_items' =>
                $processed,

                'last_guest_number' =>
                $lastGuest,

                'status' =>
                $completed
                    ? 'completed'
                    : 'processing',

                'completed_at' =>
                $completed
                    ? now()
                    : null,
            ]);

            return response()->json(
                $this->exportResponse(
                    $event,
                    $export->fresh()
                )
            );
        } catch (\Throwable $exception) {

            $export->update([
                'error_message' =>
                $exception->getMessage(),
            ]);

            return response()->json([
                'message' =>
                $exception->getMessage(),

                'file' =>
                app()->isLocal()
                    ? $exception->getFile()
                    : null,

                'line' =>
                app()->isLocal()
                    ? $exception->getLine()
                    : null,
            ], 500);
        } finally {
            $lock->release();
        }
    }


    public function download(Event $event,Export $export): StreamedResponse {
        $this->authorizeExport(
            $event,
            $export
        );

        abort_unless(
            $export->status === 'completed',
            404
        );

        abort_unless(
            $export->file_path
                && Storage::disk('local')
                ->exists($export->file_path),
            404
        );

        return Storage::disk('local')
            ->download(
                $export->file_path,
                $export->file_name
            );
    }


    private function authorizeExport(
        Event $event,
        Export $export
    ): void {
        abort_unless(
            $export->event_id
                === $event->id,
            404
        );

        abort_unless(
            $export->user_id
                === auth()->id(),
            403
        );
    }


    private function exportResponse(
        Event $event,
        Export $export
    ): array {
        $processUrl = match ($export->type) {
            'with_design' =>
            route(
                'admin.events.exports.design.process',
                [
                    $event,
                    $export,
                ]
            ),

            default =>
            route(
                'admin.events.exports.process',
                [
                    $event,
                    $export,
                ]
            ),
        };


        return [
            'id' =>
            $export->id,

            'type' =>
            $export->type,

            'status' =>
            $export->status,

            'total' =>
            $export->total_items,

            'processed' =>
            $export->processed_items,

            'progress' =>
            $export->progress,

            'process_url' =>
            $processUrl,

            'download_url' =>
            $export->status
                === 'completed'
                ? route(
                    'admin.events.exports.download',
                    [
                        $event,
                        $export,
                    ]
                )
                : null,
        ];
    }

    public function startDesign(Event $event): JsonResponse
    {
        $design = $event->design;

        if (
            !$design
            || !$design->background_path
            || !$design->design_data
        ) {
            return response()->json([
                'message' => 'Design invitation belum lengkap.',
            ], 422);
        }

        if (
            !Storage::disk('public')
                ->exists($design->background_path)
        ) {
            return response()->json([
                'message' => 'Background invitation tidak ditemukan.',
            ], 422);
        }

        $existingExport = Export::query()
            ->where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->where('type', 'with_design')
            ->where('status', 'processing')
            ->latest()
            ->first();

        if ($existingExport) {
            return response()->json(
                $this->exportResponse(
                    $event,
                    $existingExport
                )
            );
        }

        $maxGuestNumber = $event
            ->invitations()
            ->max('guest_number');

        if (!$maxGuestNumber) {
            return response()->json([
                'message' => 'Belum ada invitation.',
            ], 422);
        }

        $total = $event
            ->invitations()
            ->where(
                'guest_number',
                '<=',
                $maxGuestNumber
            )
            ->count();

        $export = Export::create([
            'event_id' => $event->id,
            'user_id' => auth()->id(),

            'type' => 'with_design',
            'status' => 'processing',

            'total_items' => $total,
            'processed_items' => 0,

            'max_guest_number' => $maxGuestNumber,

            'meta' => [
                'background_path' =>
                $design->background_path,

                'canvas_width' =>
                $design->canvas_width,

                'canvas_height' =>
                $design->canvas_height,

                'design_data' =>
                $design->design_data,
            ],
        ]);

        $directory = 'exports/' . $event->id;

        Storage::disk('local')
            ->makeDirectory($directory);

        $fileName =
            Str::slug($event->qr_prefix)
            . '-With-Design-'
            . $export->id
            . '.zip';

        $relativePath =
            $directory . '/' . $fileName;

        $zip = new ZipArchive();

        $result = $zip->open(
            Storage::disk('local')
                ->path($relativePath),
            ZipArchive::CREATE
                | ZipArchive::OVERWRITE
        );

        if ($result !== true) {
            $export->delete();

            return response()->json([
                'message' => 'Gagal membuat ZIP.',
            ], 500);
        }

        $zip->close();

        $export->update([
            'file_name' => $fileName,
            'file_path' => $relativePath,
        ]);

        return response()->json(
            $this->exportResponse(
                $event,
                $export->fresh()
            )
        );
    }

    public function processDesign(
        Event $event,
        Export $export
    ): JsonResponse {
        $this->authorizeExport(
            $event,
            $export
        );

        abort_unless(
            $export->type === 'with_design',
            404
        );

        if ($export->status === 'completed') {
            return response()->json(
                $this->exportResponse(
                    $event,
                    $export
                )
            );
        }

        $lock = Cache::lock(
            'design-export-' . $export->id,
            120
        );

        if (!$lock->get()) {
            return response()->json([
                'message' =>
                'Export sedang diproses.',
            ], 409);
        }

        try {

            $export->refresh();

            /*
         * Rendering image lebih berat,
         * jadi mulai dari 5 per batch.
         */
            $batchSize = 5;

            $invitations = $event
                ->invitations()
                ->where(
                    'guest_number',
                    '>',
                    $export->last_guest_number ?? 0
                )
                ->where(
                    'guest_number',
                    '<=',
                    $export->max_guest_number
                )
                ->orderBy('guest_number')
                ->limit($batchSize)
                ->get();

            if ($invitations->isEmpty()) {

                $export->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                return response()->json(
                    $this->exportResponse(
                        $event,
                        $export->fresh()
                    )
                );
            }


            /*
         * Generate QR batch secara parallel.
         */
            $responses = Http::pool(
                function (Pool $pool) use (
                    $invitations,
                    $event,
                    $export
                ) {
                    $requests = [];

                    $qrSettings =
                        $export->meta['design_data']['qr'];

                    foreach (
                        $invitations
                        as $invitation
                    ) {

                        $qrValue =
                            $event->qr_prefix
                            . ':'
                            . $invitation->qr_token;

                        $requests[] = $pool
                            ->as(
                                (string)
                                $invitation->id
                            )
                            ->connectTimeout(5)
                            ->timeout(20)
                            ->get(
                                'https://api.qrserver.com/v1/create-qr-code/',
                                [
                                    'size' =>
                                    '1000x1000',

                                    'data' =>
                                    $qrValue,

                                    'format' =>
                                    'png',

                                    'margin' =>
                                    $qrSettings['margin'] ?? 10,

                                    'color' =>
                                    $qrSettings['foreground'] ?? '000000',

                                    'bgcolor' =>
                                    $qrSettings['background'] ?? 'FFFFFF',
                                ]
                            );
                    }

                    return $requests;
                }
            );


            /*
         * Validate QR responses.
         */
            foreach (
                $invitations
                as $invitation
            ) {

                $response =
                    $responses[(string)
                        $invitation->id] ?? null;

                if (
                    !$response instanceof Response
                    || $response->failed()
                ) {
                    throw new RuntimeException(
                        'Gagal membuat QR '
                            . $invitation->guest_code
                    );
                }
            }


            $zipPath =
                Storage::disk('local')
                ->path(
                    $export->file_path
                );

            $zip = new ZipArchive();

            if (
                $zip->open(
                    $zipPath,
                    ZipArchive::CREATE
                ) !== true
            ) {
                throw new RuntimeException(
                    'Gagal membuka ZIP.'
                );
            }


            foreach (
                $invitations
                as $invitation
            ) {

                $response =
                    $responses[(string)
                        $invitation->id];

                $finalImage =
                    $this->renderInvitation(
                        $export,
                        $response->body()
                    );

                $baseFolder =
                    pathinfo(
                        $export->file_name,
                        PATHINFO_FILENAME
                    );

                $imagePath =
                    $baseFolder
                    . '/images/'
                    . $invitation->guest_code
                    . '.png';

                $zip->addFromString(
                    $imagePath,
                    $finalImage
                );
            }

            $processed =
                $export->processed_items
                + $invitations->count();

            $completed =
                $processed
                >= $export->total_items;


            if ($completed) {

                $excelBinary =
                    $this->generateExportExcel(
                        $event,
                        $export
                    );

                $zip->addFromString(
                    $baseFolder
                        . '/invitation-data.xlsx',
                    $excelBinary
                );
            }

            $zip->close();


            $lastGuest =
                $invitations
                ->max('guest_number');



            $export->update([
                'processed_items' =>
                $processed,

                'last_guest_number' =>
                $lastGuest,

                'status' =>
                $completed
                    ? 'completed'
                    : 'processing',

                'completed_at' =>
                $completed
                    ? now()
                    : null,
            ]);


            return response()->json(
                $this->exportResponse(
                    $event,
                    $export->fresh()
                )
            );
        } catch (\Throwable $exception) {

            $export->update([
                'error_message' =>
                $exception->getMessage(),
            ]);

            return response()->json([
                'message' =>
                $exception->getMessage(),

                'file' =>
                app()->isLocal()
                    ? $exception->getFile()
                    : null,

                'line' =>
                app()->isLocal()
                    ? $exception->getLine()
                    : null,
            ], 500);
        } finally {

            $lock->release();
        }
    }

    private function renderInvitation(
        Export $export,
        string $qrBinary
    ): string {
        $meta = $export->meta;

        $backgroundPath =
            Storage::disk('public')
            ->path(
                $meta['background_path']
            );

        $backgroundBinary =
            file_get_contents(
                $backgroundPath
            );

        $background =
            imagecreatefromstring(
                $backgroundBinary
            );

        if (!$background) {
            throw new RuntimeException(
                'Background image tidak valid.'
            );
        }

        $qrImage =
            imagecreatefromstring(
                $qrBinary
            );

        if (!$qrImage) {
            imagedestroy($background);

            throw new RuntimeException(
                'QR image tidak valid.'
            );
        }


        $canvasWidth =
            imagesx($background);

        $canvasHeight =
            imagesy($background);


        $qrSettings =
            $meta['design_data']['qr'];


        /*
     * Ratio → pixel asli.
     */
        $qrSize = (int) round(
            $canvasWidth
                * $qrSettings['size']
        );

        $qrX = (int) round(
            $canvasWidth
                * $qrSettings['x']
        );

        $qrY = (int) round(
            $canvasHeight
                * $qrSettings['y']
        );


        /*
     * Tempel QR ke invitation.
     */
        imagecopyresampled(
            $background,
            $qrImage,

            $qrX,
            $qrY,

            0,
            0,

            $qrSize,
            $qrSize,

            imagesx($qrImage),
            imagesy($qrImage)
        );


        /*
     * Output selalu PNG.
     */
        ob_start();

        imagepng(
            $background,
            null,
            6
        );

        $result =
            ob_get_clean();


        imagedestroy($background);
        imagedestroy($qrImage);


        if (!$result) {
            throw new RuntimeException(
                'Gagal render invitation.'
            );
        }

        return $result;
    }

    private function generateExportExcel(
        Event $event,
        Export $export
    ): string {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet
            ->getActiveSheet();

        $sheet->setTitle(
            'Invitation Data'
        );


        /*
     * Header
     */
        $sheet->setCellValue(
            'A1',
            'file_name'
        );

        $sheet->setCellValue(
            'B1',
            'token'
        );

        $sheet->setCellValue(
            'C1',
            'guest_name'
        );


        /*
     * Data
     */
        $row = 2;

        $invitations = $event
            ->invitations()
            ->where(
                'guest_number',
                '<=',
                $export->max_guest_number
            )
            ->orderBy('guest_number')
            ->cursor();


        foreach (
            $invitations
            as $invitation
        ) {

            $sheet->setCellValue(
                'A' . $row,
                $invitation->guest_code
                    . '.png'
            );


            /*
         * Explicit string supaya token tidak
         * diubah oleh Excel.
         */
            $sheet->setCellValueExplicit(
                'B' . $row,
                $invitation->qr_token,
                DataType::TYPE_STRING
            );


            /*
         * Sengaja kosong.
         */
            $sheet->setCellValue(
                'C' . $row,
                ''
            );

            $row++;
        }


        /*
     * Sedikit formatting.
     */
        $sheet
            ->getStyle('A1:C1')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getColumnDimension('A')
            ->setWidth(22);

        $sheet
            ->getColumnDimension('B')
            ->setWidth(35);

        $sheet
            ->getColumnDimension('C')
            ->setWidth(30);

        $sheet->freezePane('A2');


        /*
     * Generate XLSX ke memory.
     */
        $writer =
            new Xlsx($spreadsheet);

        ob_start();

        $writer->save(
            'php://output'
        );

        $excelBinary =
            ob_get_clean();


        $spreadsheet
            ->disconnectWorksheets();

        unset($spreadsheet);


        if ($excelBinary === false) {
            throw new RuntimeException(
                'Gagal membuat file Excel.'
            );
        }


        return $excelBinary;
    }
}
