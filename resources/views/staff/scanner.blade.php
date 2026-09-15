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

        <div class="max-w-lg px-4 mx-auto sm:px-6" x-data="scannerApp({
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
                    <div class="p-6 border border-green-200 rounded-2xl bg-green-50">

                        <div
                            class="flex items-center justify-center text-2xl text-green-700 bg-green-100 rounded-full size-12">
                            ✓
                        </div>

                        <h3 class="mt-4 text-xl font-semibold text-green-900">
                            Check-in Success
                        </h3>

                        <p class="mt-1 font-mono text-lg font-semibold text-green-800"
                            x-text="
                                result.guest_code
                            ">
                        </p>

                        <div class="mt-4 text-sm text-green-700">

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

                            <div class="p-3 mt-4 rounded-xl bg-green-100/70">

                                <div class="flex items-center justify-between">
                                    <span>
                                        Scan Usage
                                    </span>

                                    <span class="font-semibold"
                                        x-text="
                result.scan_count
                + ' / '
                + result.scan_limit
            "></span>
                                </div>


                                <div class="flex items-center justify-between mt-1">
                                    <span>
                                        Remaining
                                    </span>

                                    <span class="font-semibold" x-text="result.remaining_scans"></span>
                                </div>

                            </div>

                        </div>

                    </div>
                </template>


                {{-- Scan Limit Reached --}}
                <template x-if="
        result?.status
        === 'limit_reached'
    ">

                    <div class="p-6 border rounded-2xl border-amber-200 bg-amber-50">

                        <div
                            class="flex items-center justify-center text-2xl rounded-full size-12 bg-amber-100 text-amber-700">
                            !
                        </div>


                        <h3 class="mt-4 text-xl font-semibold text-amber-900">
                            Scan Limit Reached
                        </h3>


                        <p class="mt-1 font-mono text-lg font-semibold text-amber-800"
                            x-text="
                result.guest_code
            "></p>


                        <p class="mt-2 text-sm text-amber-700">
                            QR ini sudah mencapai
                            batas maksimal penggunaan.
                        </p>


                        <div class="p-3 mt-4 text-sm rounded-xl bg-amber-100/70 text-amber-800">

                            <div class="flex items-center justify-between">
                                <span>
                                    Scan Usage
                                </span>

                                <span class="font-semibold"
                                    x-text="
                        result.scan_count
                        + ' / '
                        + result.scan_limit
                    "></span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span>
                                    Remaining
                                </span>
                                <span class="font-semibold">
                                    0
                                </span>
                            </div>

                        </div>


                        <div class="mt-4 text-sm text-amber-700">

                            <template x-if="
                    result.checked_in_at
                ">
                                <div>
                                    <p>
                                        Last Scan:
                                    </p>

                                    <p class="mt-1 font-medium"
                                        x-text="
                            result.checked_in_at
                        ">
                                    </p>
                                </div>
                            </template>


                            <p x-show="
                    result.checked_in_by
                " class="mt-1">
                                By:

                                <span
                                    x-text="
                        result.checked_in_by
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
                    <div class="p-6 border border-red-200 rounded-2xl bg-red-50">

                        <div
                            class="flex items-center justify-center text-2xl text-red-700 bg-red-100 rounded-full size-12">
                            ×
                        </div>

                        <h3 class="mt-4 text-xl font-semibold text-red-900">
                            Invalid QR
                        </h3>

                        <p class="mt-2 text-sm text-red-700"
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
            <div class="overflow-hidden bg-white shadow-sm rounded-2xl">

                <div class="p-4">

                    <div id="qr-reader" class="overflow-hidden rounded-xl"></div>

                </div>


                <div class="px-5 py-4 border-t border-gray-100">

                    <div class="flex items-center justify-between">

                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                Scanner
                            </p>

                            <p class="mt-1 text-xs text-gray-500" x-text="scannerMessage"></p>
                            <button x-show="!scannerReady" x-cloak type="button" @click="retryCamera()"
                                class="px-3 py-2 mt-3 text-xs font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Try Again
                            </button>
                        </div>


                        <div class="rounded-full size-3"
                            :class="scannerReady
                                ?
                                'bg-green-500' :
                                'bg-gray-300'">
                        </div>

                    </div>

                </div>

            </div>

            {{-- Manual input fallback --}}
            <div class="p-5 mt-6 bg-white shadow-sm rounded-2xl">

                <details>

                    <summary class="text-sm font-medium text-gray-700 cursor-pointer">
                        Manual Guest Code
                    </summary>

                    <p class="mt-3 text-xs text-gray-500">
                        Enter the guest code printed below the QR.
                    </p>

                    <form class="flex gap-2 mt-4" @submit.prevent="manualCheckIn()">

                        <input type="text" x-model="manualValue" placeholder="guest00001" autocomplete="off"
                            autocapitalize="none" spellcheck="false"
                            class="flex-1 min-w-0 font-mono text-sm border-gray-300 rounded-lg">

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

</x-app-layout>
