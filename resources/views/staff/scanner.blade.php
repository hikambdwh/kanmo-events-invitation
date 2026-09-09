<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                QR Scanner
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Scan invitation QR to check-in.
            </p>
        </div>
    </x-slot>


    <div class="py-8">

        <div class="mx-auto max-w-lg
                   px-4 sm:px-6" x-data="scannerApp({
                       checkInUrl: '{{ route('staff.check-in') }}',
                       csrf: '{{ csrf_token() }}'
                   })">

            {{-- Result --}}
            <div x-show="result" x-cloak class="mt-5">

                {{-- Success --}}
                <template
                    x-if="
                        result?.status
                        === 'success'
                    ">
                    <div
                        class="rounded-2xl
                               border border-green-200
                               bg-green-50
                               p-6">

                        <div
                            class="flex size-12
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-green-100
                                   text-2xl
                                   text-green-700">
                            ✓
                        </div>

                        <h3
                            class="mt-4
                                   text-xl
                                   font-semibold
                                   text-green-900">
                            Check-in Success
                        </h3>

                        <p class="mt-1
                                   font-mono
                                   text-lg
                                   font-semibold
                                   text-green-800"
                            x-text="
                                result.guest_code
                            ">
                        </p>

                        <div
                            class="mt-4
                                   text-sm
                                   text-green-700">

                            <p
                                x-text="
                                    result.event
                                ">
                            </p>

                            <p class="mt-1"
                                x-text="
                                    result.checked_in_at
                                ">
                            </p>

                        </div>

                    </div>
                </template>


                {{-- Already Checked In --}}
                <template
                    x-if="
                        result?.status
                        === 'already_checked_in'
                    ">
                    <div
                        class="rounded-2xl
                               border border-amber-200
                               bg-amber-50
                               p-6">

                        <div
                            class="flex size-12
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-amber-100
                                   text-2xl
                                   text-amber-700">
                            !
                        </div>

                        <h3
                            class="mt-4
                                   text-xl
                                   font-semibold
                                   text-amber-900">
                            QR Already Scanned
                        </h3>

                        <p class="mt-1
                                   font-mono
                                   text-lg
                                   font-semibold
                                   text-amber-800"
                            x-text="
                                result.guest_code
                            ">
                        </p>

                        <div
                            class="mt-4
                                   text-sm
                                   text-amber-700">

                            <p>
                                Previous Check-in:
                            </p>

                            <p class="mt-1
                                       font-medium"
                                x-text="
                                    result.checked_in_at
                                ">
                            </p>

                            <p x-show="
                                    result.checked_in_by
                                "
                                class="mt-1">
                                By:
                                <span
                                    x-text="
                                        result
                                            .checked_in_by
                                    "></span>
                            </p>

                        </div>

                    </div>
                </template>


                {{-- Invalid --}}
                <template
                    x-if="
                        result?.status
                        === 'invalid'
                    ">
                    <div
                        class="rounded-2xl
                               border border-red-200
                               bg-red-50
                               p-6">

                        <div
                            class="flex size-12
                                   items-center
                                   justify-center
                                   rounded-full
                                   bg-red-100
                                   text-2xl
                                   text-red-700">
                            ×
                        </div>

                        <h3
                            class="mt-4
                                   text-xl
                                   font-semibold
                                   text-red-900">
                            Invalid QR
                        </h3>

                        <p class="mt-2
                                   text-sm
                                   text-red-700"
                            x-text="
                                result.message
                            "></p>

                    </div>
                </template>

            </div>


            {{-- Scan Again --}}
            <button x-show="
                    result
                    && !processing
                " x-cloak
                type="button" @click="resumeScanner()"
                class="mt-5
                       w-full
                       rounded-xl
                       bg-[#f94242] hover:bg-[#c53434]
                       px-5 py-3
                       text-sm
                       font-medium
                       text-white">
                Scan Next QR
            </button>

            {{-- Scanner --}}
            <div class="overflow-hidden
                       rounded-2xl bg-white
                       shadow-sm">

                <div class="p-4">

                    <div id="qr-reader" class="overflow-hidden
                               rounded-xl"></div>

                </div>


                <div class="border-t
                           border-gray-100
                           px-5 py-4">

                    <div class="flex items-center
                               justify-between">

                        <div>
                            <p
                                class="text-sm
                                       font-medium
                                       text-gray-900">
                                Scanner
                            </p>

                            <p class="mt-1
                                       text-xs
                                       text-gray-500"
                                x-text="scannerMessage"></p>
                            <button x-show="!scannerReady" x-cloak type="button" @click="retryCamera()"
                                class="mt-3
           rounded-lg
           border border-gray-300
           bg-white
           px-3 py-2
           text-xs font-medium
           text-gray-700
           transition
           hover:bg-gray-50">
                                Try Again
                            </button>
                        </div>


                        <div class="size-3
                                   rounded-full"
                            :class="scannerReady
                                ?
                                'bg-green-500' :
                                'bg-gray-300'">
                        </div>

                    </div>

                </div>

            </div>

            {{-- Manual input fallback --}}
            <div class="mt-6
           rounded-2xl
           bg-white p-5
           shadow-sm">

                <details>

                    <summary
                        class="cursor-pointer
                   text-sm
                   font-medium
                   text-gray-700">
                        Manual Guest Code
                    </summary>

                    <p class="mt-3 text-xs text-gray-500">
                        Enter the guest code printed below the QR.
                    </p>

                    <form class="mt-4 flex gap-2" @submit.prevent="manualCheckIn()">

                        <input type="text" x-model="manualValue" placeholder="guest00001" autocomplete="off"
                            autocapitalize="none" spellcheck="false"
                            class="min-w-0
                       flex-1
                       rounded-lg
                       border-gray-300
                       font-mono
                       text-sm">

                        <button type="submit" :disabled="processing"
                            class="rounded-lg
                       bg-[#f94242] hover:bg-[#c53434] cursor-pointer
                       px-4 py-2
                       text-sm
                       font-medium
                       text-white
                       disabled:opacity-50">

                            <span x-show="!processing">
                                Check
                            </span>

                            <span x-show="processing" x-cloak>
                                Checking...
                            </span>

                        </button>

                    </form>

                </details>

            </div>

        </div>

    </div>

    <script>
        window.scannerApp = function(config) {
            return {
                /*
                 * ==========================================================
                 * STATE
                 * ==========================================================
                 */

                scanner: null,

                scannerReady: false,

                scannerMessage: 'Preparing camera...',

                processing: false,

                result: null,

                manualValue: '',

                lastScannedValue: null,


                /*
                 * ==========================================================
                 * INITIALIZATION
                 * ==========================================================
                 */

                async init() {
                    await this.$nextTick();

                    /*
                     * Kamera browser membutuhkan HTTPS
                     * kecuali localhost.
                     */
                    if (!window.isSecureContext) {
                        this.scannerReady = false;

                        this.scannerMessage =
                            'Scanner membutuhkan koneksi HTTPS.';

                        return;
                    }


                    /*
                     * Pastikan browser mendukung camera API.
                     */
                    if (
                        !navigator.mediaDevices ||
                        !navigator.mediaDevices.getUserMedia
                    ) {
                        this.scannerReady = false;

                        this.scannerMessage =
                            'Browser ini tidak mendukung akses kamera.';

                        return;
                    }


                    /*
                     * Buat instance scanner.
                     */
                    this.scanner = new Html5Qrcode(
                        'qr-reader'
                    );


                    try {
                        await this.startScanner();
                    } catch (error) {
                        console.error(
                            'CAMERA ERROR:',
                            error
                        );

                        this.scannerReady = false;

                        this.setCameraErrorMessage(
                            error
                        );
                    }
                },


                /*
                 * ==========================================================
                 * START CAMERA
                 * ==========================================================
                 */

                async startScanner() {
                    this.result = null;

                    this.scannerReady = false;

                    this.scannerMessage =
                        'Requesting for camera access';


                    const scannerConfig = {
                        fps: 10,

                        qrbox: {
                            width: 250,
                            height: 250,
                        },
                    };


                    /*
                     * ------------------------------------------------------
                     * ATTEMPT #1
                     *
                     * Minta browser menggunakan kamera belakang.
                     * Ini biasanya cara paling kompatibel untuk HP.
                     * ------------------------------------------------------
                     */

                    try {
                        await this.scanner.start({
                                facingMode: 'environment',
                            },

                            scannerConfig,

                            async (decodedText) => {
                                    await this.handleScan(
                                        decodedText
                                    );
                                },

                                () => {
                                    /*
                                     * Frame tidak berisi QR.
                                     * Tidak perlu melakukan apa-apa.
                                     */
                                }
                        );


                        this.scannerReady = true;

                        this.scannerMessage =
                            'Point the camera at the QR code.';

                        return;

                    } catch (error) {
                        console.warn(
                            'Environment camera failed:',
                            error
                        );
                    }


                    /*
                     * ------------------------------------------------------
                     * ATTEMPT #2
                     *
                     * Kalau facingMode gagal, ambil daftar seluruh kamera.
                     * ------------------------------------------------------
                     */

                    let cameras = [];

                    try {
                        cameras =
                            await Html5Qrcode.getCameras();
                    } catch (error) {
                        console.error(
                            'GET CAMERAS ERROR:',
                            error
                        );

                        throw error;
                    }


                    if (!cameras.length) {
                        throw new Error(
                            'Tidak ada kamera yang ditemukan.'
                        );
                    }


                    console.log(
                        'Available cameras:',
                        cameras
                    );


                    /*
                     * Cari kamera yang kemungkinan kamera belakang.
                     */
                    const backCamera =
                        cameras.find(
                            (camera) =>
                            /back|rear|environment|belakang/i
                            .test(
                                camera.label || ''
                            )
                        );


                    /*
                     * Kamera belakang ditempatkan paling depan.
                     * Sisanya menjadi fallback.
                     */
                    const orderedCameras = [
                        ...(
                            backCamera ?
                            [backCamera] :
                            []
                        ),

                        ...cameras.filter(
                            (camera) =>
                            !backCamera ||
                            camera.id !==
                            backCamera.id
                        ),
                    ];


                    let lastError = null;


                    /*
                     * ------------------------------------------------------
                     * Coba semua kamera satu per satu.
                     * ------------------------------------------------------
                     */

                    for (
                        const camera of orderedCameras
                    ) {
                        try {
                            console.log(
                                'Trying camera:',
                                camera.label,
                                camera.id
                            );


                            await this.scanner.start(
                                camera.id,

                                scannerConfig,

                                async (decodedText) => {
                                        await this.handleScan(
                                            decodedText
                                        );
                                    },

                                    () => {
                                        /*
                                         * Ignore scan failure frame.
                                         */
                                    }
                            );


                            this.scannerReady = true;

                            this.scannerMessage =
                                'Point the camera at the QR code.';


                            console.log(
                                'Camera started:',
                                camera.label
                            );


                            return;

                        } catch (error) {
                            console.warn(
                                'Camera failed:',
                                camera.label,
                                error
                            );


                            lastError = error;


                            /*
                             * Beri jeda sebelum mencoba
                             * kamera berikutnya.
                             */
                            await this.sleep(300);
                        }
                    }


                    /*
                     * Semua kamera gagal.
                     */
                    throw (
                        lastError ??
                        new Error(
                            'Semua kamera gagal dibuka.'
                        )
                    );
                },


                /*
                 * ==========================================================
                 * CAMERA ERROR MESSAGE
                 * ==========================================================
                 */

                setCameraErrorMessage(error) {
                    /*
                     * html5-qrcode kadang melempar Error object,
                     * kadang hanya string.
                     *
                     * Jadi kita cek keduanya.
                     */

                    const name =
                        error?.name ?? '';

                    const message =
                        error?.message ??
                        String(error ?? '');


                    const fullError =
                        `${name} ${message}`;


                    console.error(
                        'FULL CAMERA ERROR:',
                        fullError
                    );


                    /*
                     * Permission ditolak.
                     */
                    if (
                        name === 'NotAllowedError' ||
                        fullError.includes(
                            'NotAllowedError'
                        ) ||
                        fullError.includes(
                            'Permission denied'
                        )
                    ) {
                        this.scannerMessage =
                            'Akses kamera ditolak. Izinkan akses kamera pada browser lalu tekan Try Again.';

                        return;
                    }


                    /*
                     * Kamera ditemukan tetapi tidak bisa dibuka.
                     *
                     * Ini error yang saat ini muncul di HP kamu:
                     * NotReadableError: Could not start video source
                     */
                    if (
                        name === 'NotReadableError' ||
                        fullError.includes(
                            'NotReadableError'
                        ) ||
                        fullError.includes(
                            'Could not start video source'
                        ) ||
                        fullError.includes(
                            'Could not start video'
                        )
                    ) {
                        this.scannerMessage =
                            'Kamera ditemukan tetapi tidak dapat dibuka. Tutup aplikasi lain yang memakai kamera, lalu tekan Try Again.';

                        return;
                    }


                    /*
                     * Tidak ada kamera.
                     */
                    if (
                        name === 'NotFoundError' ||
                        fullError.includes(
                            'NotFoundError'
                        )
                    ) {
                        this.scannerMessage =
                            'Kamera tidak ditemukan pada perangkat ini.';

                        return;
                    }


                    /*
                     * Camera constraint tidak cocok.
                     */
                    if (
                        name === 'OverconstrainedError' ||
                        fullError.includes(
                            'OverconstrainedError'
                        )
                    ) {
                        this.scannerMessage =
                            'Kamera tidak mendukung konfigurasi scanner.';

                        return;
                    }


                    /*
                     * Browser/security problem.
                     */
                    if (
                        fullError.includes(
                            'secure context'
                        )
                    ) {
                        this.scannerMessage =
                            'Scanner membutuhkan koneksi HTTPS.';

                        return;
                    }


                    /*
                     * Generic fallback.
                     */
                    this.scannerMessage =
                        message ||
                        'Kamera tidak dapat diakses.';
                },


                /*
                 * ==========================================================
                 * RETRY CAMERA
                 * ==========================================================
                 */

                async retryCamera() {
                    if (this.processing) {
                        return;
                    }


                    this.scannerReady = false;

                    this.scannerMessage =
                        'Mencoba membuka kamera...';


                    /*
                     * Bersihkan scanner lama terlebih dahulu.
                     */
                    if (this.scanner) {
                        try {
                            await this.scanner.stop();
                        } catch (_) {
                            /*
                             * Bisa gagal jika scanner
                             * memang belum sempat start.
                             */
                        }


                        try {
                            this.scanner.clear();
                        } catch (_) {}
                    }


                    /*
                     * Tunggu sedikit supaya camera resource
                     * benar-benar dilepas browser.
                     */
                    await this.sleep(500);


                    /*
                     * Buat instance baru.
                     */
                    this.scanner =
                        new Html5Qrcode(
                            'qr-reader'
                        );


                    try {
                        await this.startScanner();

                    } catch (error) {
                        console.error(
                            'RETRY CAMERA ERROR:',
                            error
                        );


                        this.scannerReady = false;

                        this.setCameraErrorMessage(
                            error
                        );
                    }
                },


                /*
                 * ==========================================================
                 * QR DETECTED
                 * ==========================================================
                 */

                async handleScan(value) {
                    /*
                     * Jangan proses QR kalau:
                     *
                     * - request masih berjalan
                     * - QR sama sudah baru saja dibaca
                     */
                    if (
                        this.processing ||
                        value ===
                        this.lastScannedValue
                    ) {
                        return;
                    }


                    this.processing = true;

                    this.lastScannedValue =
                        value;


                    console.log(
                        'QR detected:',
                        value
                    );


                    /*
                     * Pause kamera supaya QR yang sama
                     * tidak dibaca berkali-kali.
                     */
                    try {
                        this.scanner.pause(
                            true
                        );
                    } catch (error) {
                        console.warn(
                            'Unable to pause scanner:',
                            error
                        );
                    }


                    await this.checkIn(
                        value
                    );
                },


                /*
                 * ==========================================================
                 * CHECK-IN REQUEST
                 * ==========================================================
                 */

                async checkIn(value) {
                    try {
                        const response =
                            await fetch(
                                config.checkInUrl, {
                                    method: 'POST',

                                    headers: {
                                        'Accept': 'application/json',

                                        'Content-Type': 'application/json',

                                        'X-CSRF-TOKEN': config.csrf,
                                    },

                                    body: JSON.stringify({
                                        qr_value: value,
                                    }),
                                }
                            );


                        /*
                         * Response Laravel biasanya JSON.
                         */
                        let data;

                        try {
                            data =
                                await response.json();
                        } catch (_) {
                            throw new Error(
                                'Server memberikan response yang tidak valid.'
                            );
                        }


                        console.log(
                            'Check-in response:',
                            response.status,
                            data
                        );


                        /*
                         * Tetap tampilkan hasil:
                         *
                         * success
                         * already_checked_in
                         * invalid
                         */
                        this.result =
                            data;


                        /*
                         * 404, 409 dan 422 adalah
                         * response bisnis yang valid.
                         *
                         * 409:
                         * QR sudah digunakan.
                         *
                         * 404:
                         * QR/event tidak ditemukan.
                         *
                         * 422:
                         * format invalid.
                         */
                        if (
                            !response.ok &&
                            ![
                                404,
                                409,
                                422,
                            ].includes(
                                response.status
                            )
                        ) {
                            throw new Error(
                                data.message ??
                                'Check-in gagal.'
                            );
                        }

                    } catch (error) {
                        console.error(
                            'CHECK-IN ERROR:',
                            error
                        );


                        this.result = {
                            status: 'invalid',

                            message: error?.message ??
                                'Terjadi kesalahan saat check-in.',
                        };

                    } finally {
                        this.processing =
                            false;
                    }
                },


                /*
                 * ==========================================================
                 * SCAN NEXT QR
                 * ==========================================================
                 */

                async resumeScanner() {
                    this.result = null;

                    this.lastScannedValue =
                        null;


                    /*
                     * Kalau scanner masih aktif dalam keadaan pause,
                     * cukup resume.
                     */
                    try {
                        this.scanner.resume();

                        this.scannerReady =
                            true;

                        this.scannerMessage =
                            'Point the camera at the QR code.';

                        return;

                    } catch (error) {
                        console.warn(
                            'Resume failed, restarting scanner:',
                            error
                        );
                    }


                    /*
                     * Kalau resume gagal,
                     * start ulang kamera.
                     */
                    try {
                        await this.retryCamera();

                    } catch (error) {
                        console.error(
                            'Restart scanner failed:',
                            error
                        );
                    }
                },


                /*
                 * ==========================================================
                 * MANUAL INPUT
                 * ==========================================================
                 */

                async manualCheckIn() {
                    const value =
                        this.manualValue
                        .trim();


                    if (
                        !value ||
                        this.processing
                    ) {
                        return;
                    }


                    this.result =
                        null;

                    this.processing =
                        true;


                    /*
                     * CATATAN:
                     *
                     * Saat ini value yang dikirim adalah
                     * guest00001.
                     *
                     * Backend harus mendukung manual guest code.
                     */
                    await this.checkIn(
                        value
                    );


                    this.manualValue =
                        '';
                },


                /*
                 * ==========================================================
                 * HELPER
                 * ==========================================================
                 */

                sleep(ms) {
                    return new Promise(
                        (resolve) => {
                            setTimeout(
                                resolve,
                                ms
                            );
                        }
                    );
                },
            };
        };
    </script>

</x-app-layout>
