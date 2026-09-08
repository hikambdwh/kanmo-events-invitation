<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">
                QR Scanner
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Scan invitation QR untuk check-in.
            </p>
        </div>
    </x-slot>


    <div class="py-8">

        <div class="mx-auto max-w-lg
                   px-4 sm:px-6" x-data="scannerApp({
                       checkInUrl: '{{ route('staff.check-in') }}',
                   
                       csrf: '{{ csrf_token() }}'
                   })" x-init="init()">

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
                            QR Sudah Check-in
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
                       bg-gray-900
                       px-5 py-3
                       text-sm
                       font-medium
                       text-white
                       hover:bg-black">
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
                        Masukkan guest code yang tercetak di bawah QR.
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
                       bg-gray-900
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

                scanner: null,

                scannerReady: false,

                scannerMessage: 'Preparing camera...',

                processing: false,

                result: null,

                manualValue: '',

                lastScannedValue: null,


                async init() {

                    await this.$nextTick();

                    this.scanner =
                        new Html5Qrcode(
                            'qr-reader'
                        );

                    try {

                        await this.startScanner();

                    } catch (error) {
                        console.error('CAMERA ERROR:', error);

                        this.scannerReady = false;

                        this.scannerMessage =
                            `${error.name ?? 'Error'}: ${error.message ?? error}`;
                    }
                },


                async startScanner() {

                    this.result = null;

                    this.scannerMessage =
                        'Starting camera...';


                    const cameras =
                        await Html5Qrcode
                        .getCameras();


                    if (!cameras.length) {

                        throw new Error(
                            'Camera tidak ditemukan.'
                        );
                    }


                    /*
                     * Prioritaskan kamera belakang.
                     */
                    const backCamera =
                        cameras.find(
                            camera =>
                            /back|rear|environment/i
                            .test(
                                camera.label
                            )
                        );


                    const cameraId =
                        backCamera ?
                        backCamera.id :
                        cameras[
                            cameras.length - 1
                        ].id;


                    await this.scanner.start(

                        cameraId,

                        {
                            fps: 10,

                            qrbox: {
                                width: 250,
                                height: 250,
                            },

                            aspectRatio: 1,
                        },

                        async decodedText => {

                                await this
                                    .handleScan(
                                        decodedText
                                    );

                            },

                            () => {
                                // ignore scan failure frames
                            }
                    );


                    this.scannerReady =
                        true;

                    this.scannerMessage =
                        'Arahkan kamera ke QR.';
                },


                async handleScan(value) {

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


                    /*
                     * Pause kamera supaya QR
                     * tidak terbaca berkali-kali.
                     */
                    try {
                        this.scanner.pause(true);
                    } catch (_) {}


                    await this.checkIn(value);
                },


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


                        const data =
                            await response.json();


                        /*
                         * 409 bukan network error.
                         * Itu berarti QR memang
                         * sudah digunakan.
                         */
                        this.result = data;


                        if (
                            !response.ok &&
                            ![
                                404,
                                409,
                                422
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

                        this.result = {
                            status: 'invalid',

                            message: error.message ??
                                'Terjadi kesalahan.',
                        };

                    } finally {

                        this.processing =
                            false;
                    }
                },


                async resumeScanner() {

                    this.result = null;

                    this.lastScannedValue =
                        null;


                    try {

                        this.scanner.resume();

                        this.scannerMessage =
                            'Arahkan kamera ke QR.';

                    } catch (error) {

                        /*
                         * Kalau scanner sudah stop,
                         * coba start ulang.
                         */
                        await this.startScanner();
                    }
                },


                async manualCheckIn() {

                    const value =
                        this.manualValue.trim();


                    if (!value) {
                        return;
                    }


                    this.result = null;

                    this.processing = true;


                    await this.checkIn(value);

                    this.manualValue = '';
                },

            };
        };
    </script>

</x-app-layout>
