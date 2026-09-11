<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <div>

                <a href="{{ route('admin.events.invitations.index', $event) }}"
                    class="text-sm text-gray-500 hover:text-gray-900">
                    ← Invitations
                </a>

                <h2
                    class="mt-1 text-xl font-semibold text-gray-900">
                    Design Invitation
                </h2>

            </div>

        </div>

    </x-slot>


    <div class="py-12">

        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div
                    class="px-4 py-3 mb-6 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50">
                    {{ session('success') }}
                </div>
            @endif


            @if ($errors->any())
                <div
                    class="px-4 py-3 mb-6 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Upload Background --}}
            <div class="p-5 mb-6 bg-white shadow-sm rounded-xl">
                <form method="POST" enctype="multipart/form-data"
                    action="{{ route('admin.events.design.background', $event) }}" x-data="{ fileName: '' }"
                    class="flex justify-between gap-4 lg:flex-row lg:items-center">
                    @csrf

                    <div class="min-w-0 lg:w-64">
                        <h3 class="font-semibold text-gray-900">
                            Invitation Background
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            PNG or JPG. Max 10 MB.
                        </p>
                    </div>

                    <div class="flex inline-flex items-center gap-4">
                        <label
                            class="flex items-center gap-3 px-4 py-3 transition border border-gray-300 border-dashed cursor-pointer min-h-14 rounded-xl max-w-fit bg-gray-50 hover:border-gray-500 hover:bg-gray-100">
                            <div
                                class="flex items-center justify-center text-gray-500 bg-white rounded-lg shadow-sm size-10 shrink-0">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 16V4m0 0-4 4m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4" />
                                </svg>
                            </div>

                            <div class="min-w-0">

                                <p class="text-sm font-medium text-gray-700 truncate"
                                    x-text="fileName || 'Choose invitation image'"></p>

                                <p class="mt-0.5 text-xs text-gray-400">
                                    Click to browse PNG or JPG
                                </p>

                            </div>

                            <input type="file" name="background" accept="image/png,image/jpeg" class="sr-only"
                                required
                                @change="
                    fileName =
                        $event.target.files[0]
                            ? $event.target.files[0].name
                            : ''
                ">
                        </label>

                        <button type="submit"
                            class="shrink-0 rounded-xl cursor-pointer bg-[#f94242] hover:bg-[#c53434]
                   px-5 py-3 text-sm font-medium
                   text-white transition">
                            Upload
                        </button>
                    </div>
                </form>

            </div>


            @if ($design->background_path)
                @php
                    $qr = $design->design_data['qr'] ?? [
                        'x' => 0.7,
                        'y' => 0.7,
                        'size' => 0.2,
                        'foreground' => '000000',
                        'background' => 'FFFFFF',
                        'margin' => 10,
                    ];
                @endphp


                <div x-data="invitationDesigner({
                    previewValue: @js($previewValue),
                
                    x: {{ $qr['x'] }},
                
                    y: {{ $qr['y'] }},
                
                    size: {{ $qr['size'] }},
                
                    foreground: '{{ $qr['foreground'] }}',
                
                    background: '{{ $qr['background'] }}',
                
                    margin: {{ $qr['margin'] }}
                })" @pointermove.window="moveDrag($event)" @pointerup.window="endDrag()"
                    class="grid items-start gap-6
       xl:grid-cols-[minmax(0,1fr)_320px]">

                    {{-- Canvas --}}
                    <div class="bg-white shadow-sm rounded-xl">

                        <div
                            class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    Invitation Preview
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Drag the QR to change the position.
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-gray-400">
                                    Original Size
                                </p>

                                <p class="mt-0.5 text-xs font-medium text-gray-600">
                                    {{ $design->canvas_width }}
                                    ×
                                    {{ $design->canvas_height }}
                                </p>
                            </div>
                        </div>


                        <div class="p-4 sm:p-5">

                            <div
                                class="flex min-h-[500px]
                   items-start justify-center
                   overflow-auto
                   rounded-xl
                   bg-gray-100
                   p-4">

                                <div x-ref="canvas"
                                    class="relative inline-block max-w-full overflow-hidden bg-white shadow-lg">

                                    <img src="{{ Storage::disk('public')->url($design->background_path) }}"
                                        class="block h-auto w-auto
                           max-h-[68vh]
                           max-w-full
                           select-none
                           object-contain"
                                        draggable="false">

                                    <img :src="qrUrl()" @pointerdown.prevent="startDrag($event)"
                                        draggable="false"
                                        class="absolute border-2 border-blue-500 shadow-lg cursor-move select-none"
                                        :style="`
                                                                                                                                                                                                                                left: ${x * 100}%;
                                                                                                                                                                                                                                top: ${y * 100}%;
                                                                                                                                                                                                                                width: ${size * 100}%;
                                                                                                                                                                                                                            `">

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Settings --}}

                    <div
                        class="p-6 bg-white shadow-sm rounded-xl xl:sticky xl:top-6">

                        <h3 class="font-semibold text-gray-900">
                            QR Settings
                        </h3>


                        <form method="POST" action="{{ route('admin.events.design.update', $event) }}"
                            class="mt-6 space-y-6">

                            @csrf
                            @method('PUT')


                            <input type="hidden" name="qr_x" :value="x">

                            <input type="hidden" name="qr_y" :value="y">

                            <input type="hidden" name="qr_size" :value="size">

                            <input type="hidden" name="qr_foreground" :value="foreground">

                            <input type="hidden" name="qr_background" :value="background">

                            <input type="hidden" name="qr_margin" :value="margin">


                            {{-- QR Size --}}

                            <div>

                                <div class="flex justify-between">

                                    <label
                                        class="text-sm font-medium text-gray-700">
                                        QR Size
                                    </label>

                                    <span class="text-sm text-gray-500"
                                        x-text="
                                            Math.round(
                                                size * 100
                                            ) + '%'
                                        "></span>

                                </div>


                                <input type="range" min="0.05" max="0.5" step="0.005" x-model.number="size"
                                    class="w-full mt-3">

                            </div>


                            {{-- Foreground --}}

                            <div>

                                <label
                                    class="text-sm font-medium text-gray-700">
                                    QR Color
                                </label>

                                <div class="flex items-center gap-3 mt-2">

                                    <input type="color" :value="'#' + foreground"
                                        @input="
                                            foreground =
                                                $event
                                                    .target
                                                    .value
                                                    .substring(1)
                                        "
                                        class="size-10">

                                    <span
                                        class="font-mono text-sm text-gray-500"
                                        x-text="
                                            '#' + foreground
                                        "></span>

                                </div>

                            </div>


                            {{-- Background --}}

                            <div>

                                <label
                                    class="text-sm font-medium text-gray-700">
                                    QR Background
                                </label>

                                <div class="flex items-center gap-3 mt-2">

                                    <input type="color" :value="'#' + background"
                                        @input="
                                            background =
                                                $event
                                                    .target
                                                    .value
                                                    .substring(1)
                                        "
                                        class="size-10">

                                    <span
                                        class="font-mono text-sm text-gray-500"
                                        x-text="
                                            '#' + background
                                        "></span>

                                </div>

                            </div>


                            {{-- Margin --}}

                            <div>

                                <label
                                    class="text-sm font-medium text-gray-700">
                                    QR Margin
                                </label>

                                <input type="range" min="0" max="30" step="1"
                                    x-model.number="margin" class="w-full mt-3">

                                <p class="mt-1 text-xs text-gray-500"
                                    x-text="margin"></p>

                            </div>


                            <button type="submit"
                                class="w-full cursor-pointer
                                       rounded-lg
                                       bg-[#f94242] hover:bg-[#c53434]
                                       px-4 py-3
                                       text-sm
                                       font-medium
                                       text-white">
                                Save Design
                            </button>

                        </form>

                        <div x-data="designExport({
                            startUrl: '{{ route('admin.events.exports.design.start', $event) }}',
                        
                            csrf: '{{ csrf_token() }}'
                        })" class="mt-4">

                            <button type="button" @click="startExport()" :disabled="processing"
                                class="w-full rounded-lg
               border border-[#f94242] cursor-pointer
               bg-white
               px-4 py-3
               text-sm font-medium
               text-gray-900
               transition
               hover:bg-gray-50
               disabled:cursor-wait
               disabled:opacity-50">

                                <span x-show="!processing">
                                    Export With Design
                                </span>

                                <span x-show="processing" x-cloak
                                    x-text="
                `Generating ${processed}/${total}...`
            "></span>

                            </button>


                            {{-- Progress --}}

                            <div x-show="processing" x-cloak class="mt-4">

                                <div class="h-2 overflow-hidden bg-gray-100 rounded-full">
                                    <div class="h-full bg-[#f94242] transition-all" :style="`width: ${progress}%`">
                                    </div>

                                </div>

                                <div class="flex justify-between mt-2 text-xs text-gray-500">
                                    <span x-text="processed + ' / ' + total"></span>
                                    <span x-text="progress + '%'"></span>
                                </div>
                            </div>


                            {{-- Completed --}}

                            <div x-show="status === 'completed'" x-cloak
                                class="p-4 mt-4 border border-green-200 rounded-lg bg-green-50">
                                <p class="text-sm font-medium text-green-700">
                                    Export Completed.
                                </p>
                                <a :href="downloadUrl"
                                    class="mt-3 block
                                    rounded-lg
                                    bg-[#f94242] hover:bg-[#c53434] cursor-pointer
                                    px-4 py-2.5
                                    text-center
                                    text-sm font-medium
                                    text-white">
                                    Download ZIP
                                </a>

                            </div>


                            {{-- Error --}}

                            <div x-show="error" x-cloak
                                class="p-4 mt-4 border border-red-200 rounded-lg bg-red-50">

                                <p class="text-smtext-red-600" x-text="error"></p>
                                <button type="button" @click="continueExport()"
                                    class="mt-3 text-sm font-medium text-red-700">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div
                    class="px-6 py-20 text-center bg-white border-2 border-gray-300 border-dashed rounded-xl">

                    <p class="font-medium text-gray-700">
                        Upload invitation card
                        terlebih dahulu.
                    </p>

                </div>
            @endif

        </div>

    </div>
</x-app-layout>
