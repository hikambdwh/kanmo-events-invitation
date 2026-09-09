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

</x-app-layout>
