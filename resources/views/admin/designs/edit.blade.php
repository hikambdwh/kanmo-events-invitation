<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center
                   justify-between">

            <div>

                <a href="{{ route('admin.events.invitations.index', $event) }}"
                    class="text-sm
                           text-gray-500
                           hover:text-gray-900">
                    ← Invitations
                </a>

                <h2
                    class="mt-1
                           text-xl
                           font-semibold
                           text-gray-900">
                    Design Invitation
                </h2>

            </div>

        </div>

    </x-slot>


    <div class="py-12">

        <div class="mx-auto max-w-7xl
                   px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div
                    class="mb-6 rounded-lg
                           border border-green-200
                           bg-green-50
                           px-4 py-3
                           text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif


            @if ($errors->any())
                <div
                    class="mb-6 rounded-lg
                           border border-red-200
                           bg-red-50
                           px-4 py-3
                           text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Upload Background --}}
            <div class="mb-6 rounded-xl bg-white p-5 shadow-sm">
                <form method="POST" enctype="multipart/form-data"
                    action="{{ route('admin.events.design.background', $event) }}" x-data="{ fileName: '' }"
                    class="flex justify-between gap-4 lg:flex-row lg:items-center">
                    @csrf

                    <div class="min-w-0 lg:w-64">
                        <h3 class="font-semibold text-gray-900">
                            Invitation Background
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            PNG atau JPG. Maksimal 10 MB.
                        </p>
                    </div>

                    <div class="flex inline-flex items-center gap-4">
                        <label
                            class="flex min-h-14 cursor-pointer items-center gap-3
                   rounded-xl border max-w-fit border-dashed border-gray-300
                   bg-gray-50 px-4 py-3 transition
                   hover:border-gray-500 hover:bg-gray-100">
                            <div
                                class="flex size-10 shrink-0 items-center
                       justify-center rounded-lg bg-white
                       text-gray-500 shadow-sm">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 16V4m0 0-4 4m4-4 4 4M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4" />
                                </svg>
                            </div>

                            <div class="min-w-0">

                                <p class="truncate text-sm font-medium text-gray-700"
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
                            class="shrink-0 rounded-xl bg-gray-900
                   px-5 py-3 text-sm font-medium
                   text-white transition hover:bg-black">
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
                    <div class="rounded-xl bg-white shadow-sm">

                        <div
                            class="flex items-center justify-between
               border-b border-gray-100
               px-5 py-4">
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    Invitation Preview
                                </h3>

                                <p class="mt-1 text-xs text-gray-500">
                                    Drag QR untuk mengubah posisi.
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
                                    class="relative inline-block
                       max-w-full
                       overflow-hidden
                       bg-white
                       shadow-lg">

                                    <img src="{{ Storage::disk('public')->url($design->background_path) }}"
                                        class="block h-auto w-auto
                           max-h-[68vh]
                           max-w-full
                           select-none
                           object-contain"
                                        draggable="false">

                                    <img :src="qrUrl()" @pointerdown.prevent="startDrag($event)"
                                        draggable="false"
                                        class="absolute cursor-move
                           select-none border-2
                           border-blue-500 shadow-lg"
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
                        class="rounded-xl
           bg-white p-6
           shadow-sm
           xl:sticky
           xl:top-6">

                        <h3 class="font-semibold
                                   text-gray-900">
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

                                <div class="flex
                                           justify-between">

                                    <label
                                        class="text-sm
                                               font-medium
                                               text-gray-700">
                                        QR Size
                                    </label>

                                    <span class="text-sm
                                               text-gray-500"
                                        x-text="
                                            Math.round(
                                                size * 100
                                            ) + '%'
                                        "></span>

                                </div>


                                <input type="range" min="0.05" max="0.5" step="0.005" x-model.number="size"
                                    class="mt-3 w-full">

                            </div>


                            {{-- Foreground --}}

                            <div>

                                <label
                                    class="text-sm
                                           font-medium
                                           text-gray-700">
                                    QR Color
                                </label>

                                <div class="mt-2 flex
                                           items-center gap-3">

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
                                        class="font-mono
                                               text-sm
                                               text-gray-500"
                                        x-text="
                                            '#' + foreground
                                        "></span>

                                </div>

                            </div>


                            {{-- Background --}}

                            <div>

                                <label
                                    class="text-sm
                                           font-medium
                                           text-gray-700">
                                    QR Background
                                </label>

                                <div class="mt-2 flex
                                           items-center gap-3">

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
                                        class="font-mono
                                               text-sm
                                               text-gray-500"
                                        x-text="
                                            '#' + background
                                        "></span>

                                </div>

                            </div>


                            {{-- Margin --}}

                            <div>

                                <label
                                    class="text-sm
                                           font-medium
                                           text-gray-700">
                                    QR Margin
                                </label>

                                <input type="range" min="0" max="30" step="1"
                                    x-model.number="margin" class="mt-3 w-full">

                                <p class="mt-1
                                           text-xs
                                           text-gray-500"
                                    x-text="margin"></p>

                            </div>


                            <button type="submit"
                                class="w-full
                                       rounded-lg
                                       bg-gray-900
                                       px-4 py-3
                                       text-sm
                                       font-medium
                                       text-white
                                       hover:bg-black">
                                Save Design
                            </button>

                        </form>

                        <div x-data="designExport({
                            startUrl: '{{ route('admin.events.exports.design.start', $event) }}',
                        
                            csrf: '{{ csrf_token() }}'
                        })" class="mt-4">

                            <button type="button" @click="startExport()" :disabled="processing"
                                class="w-full rounded-lg
               border border-gray-900
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

                                <div
                                    class="h-2 overflow-hidden
                   rounded-full
                   bg-gray-100">

                                    <div class="h-full
                       bg-gray-900
                       transition-all"
                                        :style="`width: ${progress}%`"></div>

                                </div>

                                <div
                                    class="mt-2
                   flex justify-between
                   text-xs
                   text-gray-500">

                                    <span
                                        x-text="
                    processed
                    + ' / '
                    + total
                "></span>

                                    <span x-text="
                    progress + '%'
                "></span>

                                </div>

                            </div>


                            {{-- Completed --}}

                            <div x-show="
            status === 'completed'
        " x-cloak
                                class="mt-4 rounded-lg
               border border-green-200
               bg-green-50 p-4">

                                <p class="text-sm
                   font-medium
                   text-green-700">
                                    Export berhasil.
                                </p>


                                <a :href="downloadUrl"
                                    class="mt-3 block
                   rounded-lg
                   bg-gray-900
                   px-4 py-2.5
                   text-center
                   text-sm font-medium
                   text-white">
                                    Download ZIP
                                </a>

                            </div>


                            {{-- Error --}}

                            <div x-show="error" x-cloak
                                class="mt-4 rounded-lg
               border border-red-200
               bg-red-50 p-4">

                                <p class="text-sm
                   text-red-600" x-text="error"></p>


                                <button type="button" @click="continueExport()"
                                    class="mt-3
                   text-sm
                   font-medium
                   text-red-700">
                                    Try Again
                                </button>

                            </div>

                        </div>

                    </div>

                </div>
            @else
                <div
                    class="rounded-xl
                           border-2
                           border-dashed
                           border-gray-300
                           bg-white
                           px-6 py-20
                           text-center">

                    <p class="font-medium
                               text-gray-700">
                        Upload invitation card
                        terlebih dahulu.
                    </p>

                </div>
            @endif

        </div>

    </div>
    <script>
        window.designExport = function(config) {

            return {

                processing: false,

                status: null,

                processed: 0,

                total: 0,

                progress: 0,

                processUrl: null,

                downloadUrl: null,

                error: null,


                async startExport() {

                    if (this.processing) {
                        return;
                    }

                    this.error = null;
                    this.processing = true;


                    try {

                        const response =
                            await fetch(
                                config.startUrl, {
                                    method: 'POST',

                                    headers: {
                                        'Accept': 'application/json',

                                        'Content-Type': 'application/json',

                                        'X-CSRF-TOKEN': config.csrf,
                                    },
                                }
                            );


                        const data =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                data.message ??
                                'Gagal memulai export.'
                            );
                        }


                        this.apply(data);


                        if (
                            this.status !==
                            'completed'
                        ) {
                            await this.processLoop();
                        }


                    } catch (error) {

                        this.error =
                            error.message;

                        this.processing =
                            false;
                    }
                },


                async processLoop() {

                    while (
                        this.status ===
                        'processing'
                    ) {

                        try {

                            const response =
                                await fetch(
                                    this.processUrl, {
                                        method: 'POST',

                                        headers: {
                                            'Accept': 'application/json',

                                            'Content-Type': 'application/json',

                                            'X-CSRF-TOKEN': config.csrf,
                                        },
                                    }
                                );


                            if (
                                response.status ===
                                409
                            ) {

                                await this.sleep(1000);

                                continue;
                            }


                            const data =
                                await response.json();


                            if (!response.ok) {

                                throw new Error(
                                    data.message ??
                                    'Render batch gagal.'
                                );
                            }


                            this.apply(data);

                            await this.sleep(300);


                        } catch (error) {

                            this.error =
                                error.message;

                            this.processing =
                                false;

                            return;
                        }
                    }


                    this.processing = false;
                },


                async continueExport() {

                    if (
                        !this.processUrl ||
                        this.processing
                    ) {
                        return;
                    }

                    this.error = null;
                    this.processing = true;

                    await this.processLoop();
                },


                apply(data) {

                    this.status =
                        data.status;

                    this.processed =
                        data.processed;

                    this.total =
                        data.total;

                    this.progress =
                        data.progress;

                    this.processUrl =
                        data.process_url;

                    this.downloadUrl =
                        data.download_url;

                    this.error = null;
                },


                sleep(ms) {

                    return new Promise(
                        resolve =>
                        setTimeout(
                            resolve,
                            ms
                        )
                    );
                },

            };
        };
    </script>

    <script>
        window.invitationDesigner = function(config) {

            return {

                previewValue: config.previewValue,

                x: Number(config.x),

                y: Number(config.y),

                size: Number(config.size),

                foreground: config.foreground,

                background: config.background,

                margin: Number(config.margin),


                dragging: false,

                pointerX: 0,

                pointerY: 0,

                startX: 0,

                startY: 0,


                qrUrl() {

                    return (
                        'https://api.qrserver.com/v1/create-qr-code/' +
                        '?size=600x600' +
                        '&data=' +
                        encodeURIComponent(
                            this.previewValue
                        ) +
                        '&color=' +
                        this.foreground +
                        '&bgcolor=' +
                        this.background +
                        '&margin=' +
                        this.margin
                    );
                },


                startDrag(event) {

                    this.dragging = true;

                    this.pointerX =
                        event.clientX;

                    this.pointerY =
                        event.clientY;

                    this.startX =
                        this.x;

                    this.startY =
                        this.y;
                },


                moveDrag(event) {

                    if (!this.dragging) {
                        return;
                    }

                    const rect =
                        this.$refs
                        .canvas
                        .getBoundingClientRect();


                    const deltaX =
                        (
                            event.clientX -
                            this.pointerX
                        ) /
                        rect.width;


                    const deltaY =
                        (
                            event.clientY -
                            this.pointerY
                        ) /
                        rect.height;


                    let newX =
                        this.startX +
                        deltaX;

                    let newY =
                        this.startY +
                        deltaY;


                    const qrHeightRatio =
                        (
                            rect.width *
                            this.size
                        ) /
                        rect.height;


                    newX = Math.max(
                        0,
                        Math.min(
                            1 - this.size,
                            newX
                        )
                    );


                    newY = Math.max(
                        0,
                        Math.min(
                            1 - qrHeightRatio,
                            newY
                        )
                    );


                    this.x = newX;
                    this.y = newY;
                },


                endDrag() {
                    this.dragging = false;
                },
            };
        };
    </script>

</x-app-layout>
