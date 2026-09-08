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
                * Tambahkan Guest Code
                * tepat di bawah QR.
                */
                $finalQrImage =
                    $this->renderQrWithGuestCode(
                        $response->body(),
                        $invitation->guest_code
                    );

                $zip->addFromString(
                    $imagePath,
                    $finalQrImage
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


    public function download(Event $event, Export $export): StreamedResponse
    {
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
                        $response->body(),
                        $invitation->guest_code
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
        string $qrBinary,
        string $guestCode
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

            imagedestroy(
                $background
            );

            throw new RuntimeException(
                'QR image tidak valid.'
            );
        }


        $canvasWidth =
            imagesx(
                $background
            );

        $canvasHeight =
            imagesy(
                $background
            );


        $qrSettings =
            $meta['design_data']['qr'];


        /*
     * Ratio editor →
     * pixel asli invitation.
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
     * ========================================
     * TEMPel QR
     * ========================================
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
     * ========================================
     * GUEST CODE
     * ========================================
     *
     * Guest code diletakkan tepat
     * di bawah QR.
     */

        $gap = max(
            6,
            (int) round(
                $qrSize * 0.02
            )
        );


        /*
     * Ukuran badge mengikuti
     * ukuran QR.
     */
        $labelHeight = max(
            36,
            (int) round(
                $qrSize * 0.14
            )
        );


        $labelX =
            $qrX;


        $labelY =
            $qrY
            + $qrSize
            + $gap;


        $labelWidth =
            $qrSize;


        /*
     * Kalau QR terlalu dekat
     * dengan bagian bawah canvas,
     * pastikan guest code tidak
     * terpotong.
     */
        if (
            $labelY
            + $labelHeight
            > $canvasHeight
        ) {

            $labelY =
                max(
                    0,
                    $canvasHeight
                        - $labelHeight
                        - 5
                );
        }


        /*
     * Background putih agar kode
     * tetap terbaca di atas design
     * dengan warna apa pun.
     */
        $white =
            imagecolorallocate(
                $background,
                255,
                255,
                255
            );


        imagefilledrectangle(
            $background,

            $labelX,
            $labelY,

            $labelX
                + $labelWidth,

            $labelY
                + $labelHeight,

            $white
        );


        /*
     * Tentukan scale tulisan.
     */
        $textScale = max(
            2,
            (int) round(
                $qrSize / 300
            )
        );


        $this->drawCenteredBitmapText(
            $background,

            strtoupper(
                $guestCode
            ),

            $labelX,
            $labelY,

            $labelWidth,
            $labelHeight,

            $textScale
        );


        /*
     * ========================================
     * OUTPUT PNG
     * ========================================
     */
        ob_start();


        imagepng(
            $background,
            null,
            6
        );


        $result =
            ob_get_clean();


        imagedestroy(
            $background
        );

        imagedestroy(
            $qrImage
        );


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

    private function renderQrWithGuestCode(
        string $qrBinary,
        string $guestCode
    ): string {

        /*
     * Convert binary QR
     * menjadi GD image.
     */
        $qrImage =
            imagecreatefromstring(
                $qrBinary
            );


        if (!$qrImage) {

            throw new RuntimeException(
                'QR image tidak valid.'
            );
        }


        $qrWidth =
            imagesx(
                $qrImage
            );

        $qrHeight =
            imagesy(
                $qrImage
            );


        /*
     * Area tambahan di bawah QR.
     */
        $bottomHeight =
            max(
                180,
                (int) round(
                    $qrHeight * 0.18
                )
            );


        $canvasWidth =
            $qrWidth;


        $canvasHeight =
            $qrHeight
            + $bottomHeight;


        /*
     * Buat canvas baru.
     */
        $canvas =
            imagecreatetruecolor(
                $canvasWidth,
                $canvasHeight
            );


        if (!$canvas) {

            imagedestroy(
                $qrImage
            );

            throw new RuntimeException(
                'Gagal membuat canvas QR.'
            );
        }


        /*
     * Background putih.
     */
        $white =
            imagecolorallocate(
                $canvas,
                255,
                255,
                255
            );


        imagefill(
            $canvas,
            0,
            0,
            $white
        );


        /*
     * Copy QR original tanpa
     * mengubah ukurannya.
     */
        imagecopy(
            $canvas,
            $qrImage,

            0,
            0,

            0,
            0,

            $qrWidth,
            $qrHeight
        );


        /*
     * ========================================
     * LABEL "GUEST CODE"
     * ========================================
     */
        $this->drawCenteredBitmapText(
            $canvas,

            'GUEST CODE',

            0,
            $qrHeight + 15,

            $canvasWidth,
            55,

            2
        );


        /*
     * ========================================
     * VALUE guest00001
     * ========================================
     */
        $this->drawCenteredBitmapText(
            $canvas,

            strtoupper(
                $guestCode
            ),

            0,
            $qrHeight + 65,

            $canvasWidth,
            $bottomHeight - 70,

            4
        );


        /*
     * Output PNG ke memory.
     */
        ob_start();


        imagepng(
            $canvas,
            null,
            6
        );


        $result =
            ob_get_clean();


        imagedestroy(
            $canvas
        );

        imagedestroy(
            $qrImage
        );


        if (!$result) {

            throw new RuntimeException(
                'Gagal membuat QR dengan guest code.'
            );
        }


        return $result;
    }

    private function drawCenteredBitmapText(
        $destination,
        string $text,
        int $areaX,
        int $areaY,
        int $areaWidth,
        int $areaHeight,
        int $requestedScale = 2
    ): void {

        /*
     * GD built-in font.
     *
     * Tidak membutuhkan file
     * .ttf tambahan.
     */
        $font = 5;


        $text =
            trim(
                $text
            );


        if ($text === '') {
            return;
        }


        $baseWidth =
            imagefontwidth(
                $font
            )
            * strlen(
                $text
            );


        $baseHeight =
            imagefontheight(
                $font
            );


        if (
            $baseWidth <= 0
            || $baseHeight <= 0
        ) {
            return;
        }


        /*
     * Cari scale maksimal agar
     * text tidak keluar dari
     * area yang tersedia.
     */
        $maxScaleByWidth =
            max(
                1,
                (int) floor(
                    ($areaWidth * 0.90)
                        / $baseWidth
                )
            );


        $maxScaleByHeight =
            max(
                1,
                (int) floor(
                    ($areaHeight * 0.80)
                        / $baseHeight
                )
            );


        $scale =
            max(
                1,
                min(
                    $requestedScale,
                    $maxScaleByWidth,
                    $maxScaleByHeight
                )
            );


        /*
     * Buat temporary transparent
     * image untuk tulisan.
     */
        $textImage =
            imagecreatetruecolor(
                $baseWidth,
                $baseHeight
            );


        imagesavealpha(
            $textImage,
            true
        );


        $transparent =
            imagecolorallocatealpha(
                $textImage,
                0,
                0,
                0,
                127
            );


        imagefill(
            $textImage,
            0,
            0,
            $transparent
        );


        $black =
            imagecolorallocate(
                $textImage,
                0,
                0,
                0
            );


        imagestring(
            $textImage,
            $font,
            0,
            0,
            $text,
            $black
        );


        $targetWidth =
            $baseWidth
            * $scale;


        $targetHeight =
            $baseHeight
            * $scale;


        /*
     * Center horizontal.
     */
        $targetX =
            $areaX
            + (int) round(
                (
                    $areaWidth
                    - $targetWidth
                ) / 2
            );


        /*
     * Center vertical.
     */
        $targetY =
            $areaY
            + (int) round(
                (
                    $areaHeight
                    - $targetHeight
                ) / 2
            );


        imagecopyresampled(
            $destination,
            $textImage,

            $targetX,
            $targetY,

            0,
            0,

            $targetWidth,
            $targetHeight,

            $baseWidth,
            $baseHeight
        );


        imagedestroy(
            $textImage
        );
    }
}
