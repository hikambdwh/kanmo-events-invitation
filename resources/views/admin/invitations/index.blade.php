<x-app-layout>

    <x-slot name="header">

        <div
            class="flex flex-col gap-4
                   sm:flex-row
                   sm:items-center
                   sm:justify-between">

            <div>

                <a href="{{ route('admin.events.show', $event) }}"
                    class="text-sm text-gray-500
                           hover:text-gray-900">
                    ← {{ $event->name }}
                </a>

                <h2
                    class="mt-1 text-xl
                           font-semibold
                           text-gray-900">
                    Invitations
                </h2>

            </div>


            <div class="flex items-center gap-3 x-data">

                <form method="POST" action="{{ route('admin.events.invitations.generate', $event) }}"
                    class="flex items-center gap-2">
                    @csrf

                    <input type="number" name="quantity" min="1" max="1000" value="10"
                        class="w-24 rounded-lg
                   border-gray-300
                   text-sm
                   focus:border-gray-900
                   focus:ring-gray-900"
                        required>

                    <button type="submit"
                        class="rounded-lg cursor-pointer bg-[#f94242] hover:bg-[#c53434] px-4 py-2.5 text-sm font-medium text-white">
                        Generate
                    </button>
                </form>


                <button x-data type="button" @click="$dispatch('open-download-modal')"
                    class="rounded-lg border border-gray-300 bg-white
           px-4 py-2.5 text-sm font-medium text-gray-700
           hover:bg-gray-50 cursor-pointer">
                    Download
                </button>

            </div>

        </div>

    </x-slot>


    <div class="py-12">

        <div class="mx-auto max-w-7xl
                   px-4 sm:px-6 lg:px-8">

            {{-- Notification --}}

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

                    <ul class="list-disc
                               space-y-1 pl-5">

                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- Statistics --}}

            <div class="grid gap-4
                       sm:grid-cols-3">

                <div class="rounded-xl bg-white
                           p-5 shadow-sm">

                    <p class="text-sm
                               text-gray-500">
                        Total Invitations
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900">
                        {{ number_format($statistics['total']) }}
                    </p>

                </div>


                <div class="rounded-xl bg-white
                           p-5 shadow-sm">

                    <p class="text-sm
                               text-gray-500">
                        Available
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900">
                        {{ number_format($statistics['available']) }}
                    </p>

                </div>


                <div class="rounded-xl bg-white
                           p-5 shadow-sm">

                    <p class="text-sm
                               text-gray-500">
                        Checked In
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900">
                        {{ number_format($statistics['checked_in']) }}
                    </p>

                </div>

            </div>


            {{-- Filter --}}

            <div class="mt-6 rounded-xl
                       bg-white p-5
                       shadow-sm">

                <div x-data="invitationFilter({
                    url: '{{ route('admin.events.invitations.index', $event) }}',
                
                    initialSearch: @js($search),
                    initialStatus: @js($status),
                })" class="mt-6">

                    {{-- Search & Filter --}}
                    <div class="rounded-xl bg-white
               p-5 shadow-sm">

                        <div class="flex flex-col gap-3
                   sm:flex-row">

                            {{-- Search --}}
                            <div class="relative flex-1">

                                <input type="text" x-model="search"
                                    @input.debounce.350ms="
                        load()
                    "
                                    placeholder="Search guest00001..." autocomplete="off"
                                    class="w-full rounded-lg
                           border-gray-300
                           pr-10
                           text-sm
                           focus:border-[#f94242]
                           focus:ring-[#f94242]">


                                {{-- Loading spinner --}}
                                <div x-show="loading" x-cloak
                                    class="absolute
                           right-3 top-1/2
                           -translate-y-1/2">

                                    <svg class="size-4
                               animate-spin
                               text-gray-400"
                                        viewBox="0 0 24 24" fill="none">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4" />

                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0
                               018-8v4a4 4
                               0 00-4 4H4z" />
                                    </svg>

                                </div>

                            </div>


                            {{-- Status --}}
                            <div>

                                <select x-model="status" @change="load()"
                                    class="w-full rounded-lg
                           border-gray-300
                           text-sm
                           sm:w-44
                           focus:border-[#f94242]
                           focus:ring-[#f94242]">

                                    <option value="">
                                        All Status
                                    </option>

                                    <option value="available">
                                        Available
                                    </option>

                                    <option value="checked_in">
                                        Scanned
                                    </option>

                                </select>

                            </div>


                            {{-- Reset --}}
                            <button type="button"
                                x-show="
                    search !== ''
                    || status !== ''
                "
                                x-cloak @click="reset()"
                                class="rounded-lg
                       border border-gray-300
                       px-5 py-2.5
                       text-sm font-medium
                       text-gray-700
                       transition
                       hover:bg-gray-50
                       cursor-pointer">
                                Reset
                            </button>

                        </div>

                    </div>


                    {{-- AJAX result --}}
                    <div class="relative mt-6" @click="
            handlePagination($event)
        ">

                        {{-- Loading overlay --}}
                        <div x-show="loading" x-cloak
                            class="absolute inset-0
                   z-20 flex
                   items-start
                   justify-center
                   rounded-xl
                   bg-white/60
                   pt-20
                   backdrop-blur-[1px]">

                            <div
                                class="flex items-center
                       gap-2
                       rounded-lg
                       bg-white
                       px-4 py-3
                       text-sm
                       text-gray-600
                       shadow">

                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />

                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0
                           018-8v4a4 4
                           0 00-4 4H4z" />
                                </svg>

                                Loading...

                            </div>

                        </div>


                        <div x-ref="results">

                            @include('admin.invitations._table', [
                                'event' => $event,
                                'invitations' => $invitations,
                            ])

                        </div>

                    </div>

                </div>

            </div>
        </div>

        <div x-data="qrExport({
            startUrl: '{{ route('admin.events.exports.qr.start', $event) }}',
            csrf: '{{ csrf_token() }}'
        })" @open-download-modal.window="downloadModal = true" x-show="downloadModal"
            x-cloak @keydown.escape.window="downloadModal = false"
            class="fixed inset-0 z-50
           flex items-center justify-center
           p-4">
            {{-- Overlay --}}
            <div x-show="downloadModal" x-transition.opacity @click="downloadModal = false"
                class="absolute inset-0
               bg-black/50"></div>


            {{-- Modal --}}
            <div x-show="downloadModal" x-transition @keydown.escape.window="downloadModal = false"
                class="relative z-10
               w-full max-w-lg
               rounded-2xl
               bg-white
               p-6
               shadow-xl">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <h3 class="text-lg font-semibold
                           text-gray-900">
                            Download Invitations
                        </h3>

                        <p class="mt-1 text-sm
                           text-gray-500">
                            Pilih format export invitation.
                        </p>
                    </div>


                    <button type="button" @click="downloadModal = false"
                        class="rounded-lg p-2
                       text-gray-400
                       hover:bg-gray-100
                       hover:text-gray-700">
                        ✕
                    </button>

                </div>


                <div class="mt-6 space-y-3">

                    {{-- QR Only --}}
                    <button type="button" @click="startExport()" :disabled="processing"
                        class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-5 text-left transition hover:border-[#f94242] hover:bg-red-50 disabled:cursor-wait disabled:opacity-60">
                        <div>

                            <p class="font-medium text-gray-900">

                                <span x-show="!processing">
                                    QR Only
                                </span>

                                <span x-show="processing" x-cloak>
                                    Preparing ZIP...
                                </span>

                            </p>

                            <p class="mt-1 text-sm
                   text-gray-500">
                                Download all QR codes in a single ZIP file.
                            </p>

                        </div>

                        <span x-show="!processing" class="text-xl text-gray-400">
                            →
                        </span>

                        <svg x-show="processing" x-cloak class="size-5 animate-spin
               text-gray-500"
                            viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />

                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4
               0 00-4 4H4z" />
                        </svg>

                    </button>

                    <div x-show="
        processing
        || status === 'completed'
        || error
    " x-cloak
                        class="rounded-xl
           border border-gray-200
           p-5">

                        <template x-if="processing">
                            <div>

                                <div class="flex items-center
                       justify-between">
                                    <p
                                        class="text-sm
                           font-medium
                           text-gray-700">
                                        Generating QR
                                    </p>

                                    <p class="text-sm
                           text-black"
                                        x-text="
                        processed
                        + ' / '
                        + total
                    ">
                                    </p>
                                </div>


                                <div
                                    class="mt-3 h-2
                       overflow-hidden
                       rounded-full
                       bg-gray-100">

                                    <div class="h-full
                           bg-[#f94242]
                           transition-all
                           duration-300"
                                        :style="`width: ${progress}%`">
                                    </div>

                                </div>


                                <p class="mt-2
                       text-right
                       text-xs
                       text-gray-500"
                                    x-text="progress + '%'"></p>

                            </div>
                        </template>


                        <template x-if="
            status === 'completed'
        ">
                            <div class="flex items-center
                   justify-between gap-4">

                                <div>
                                    <p class="font-medium
                           text-gray-900">
                                        ZIP Ready
                                    </p>

                                    <p class="mt-1
                           text-sm
                           text-gray-500"
                                        x-text="
                        total
                        + ' QR generated'
                    ">
                                    </p>
                                </div>


                                <a :href="downloadUrl"
                                    class="rounded-lg bg-[#f94242] hover:bg-[#c53434] px-4 py-2.5 text-sm font-medium text-white">
                                    Download ZIP
                                </a>

                            </div>
                        </template>


                        <template x-if="error">

                            <div>

                                <p class="text-sm
                       text-red-600" x-text="error"></p>

                                <button type="button" @click="continueExport()"
                                    class="mt-3
                       rounded-lg
                       border
                       border-gray-300
                       px-4 py-2
                       text-sm
                       font-medium
                       text-gray-700">
                                    Try Again
                                </button>

                            </div>

                        </template>

                    </div>


                    {{-- With Design --}}
                    <a href="{{ route('admin.events.design.edit', $event) }}"
                        class="flex w-full cursor-pointer items-center justify-between rounded-xl border border-gray-200 p-5 text-left transition hover:border-[#f94242] hover:bg-red-50 disabled:cursor-wait disabled:opacity-60">
                        <div>

                            <p class="font-medium text-gray-900">
                                With Design
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                Apply QR to your invitation card before download.
                            </p>

                        </div>

                        <span class="text-xl
               text-gray-400">
                            →
                        </span>

                    </a>

                </div>


                <div class="mt-6 flex
                   justify-end">
                    <button type="button" @click="downloadModal = false"
                        class="rounded-lg
                       border
                       border-gray-300
                       px-4 py-2.5
                       text-sm font-medium
                       text-gray-700
                       hover:bg-gray-50">
                        Cancel
                    </button>
                </div>

            </div>

        </div>

    </div>

    <script>
    window.invitationFilter =
        function (config) {

            return {

                search:
                    config.initialSearch ?? '',

                status:
                    config.initialStatus ?? '',

                loading:
                    false,

                controller:
                    null,


                async load(
                    pageUrl = null
                ) {

                    /*
                     * Cancel request sebelumnya.
                     *
                     * Misalnya user mengetik cepat:
                     *
                     * guest0
                     * guest00
                     * guest000
                     *
                     * kita hanya peduli request
                     * terakhir.
                     */
                    if (this.controller) {
                        this.controller.abort();
                    }


                    this.controller =
                        new AbortController();


                    this.loading = true;


                    try {

                        let url;


                        /*
                         * Pagination mengirim URL
                         * sendiri.
                         */
                        if (pageUrl) {

                            url = new URL(
                                pageUrl,
                                window.location.origin
                            );

                        } else {

                            url = new URL(
                                config.url,
                                window.location.origin
                            );

                        }


                        /*
                         * Selalu sinkronkan
                         * search & status terbaru.
                         */
                        if (
                            this.search.trim()
                            !== ''
                        ) {

                            url.searchParams.set(
                                'search',
                                this.search.trim()
                            );

                        } else {

                            url.searchParams.delete(
                                'search'
                            );
                        }


                        if (
                            this.status !== ''
                        ) {

                            url.searchParams.set(
                                'status',
                                this.status
                            );

                        } else {

                            url.searchParams.delete(
                                'status'
                            );
                        }


                        /*
                         * Kalau bukan pagination,
                         * selalu kembali ke page 1.
                         */
                        if (!pageUrl) {
                            url.searchParams.delete(
                                'page'
                            );
                        }


                        const response =
                            await fetch(
                                url.toString(),
                                {
                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },

                                    signal:
                                        this.controller
                                            .signal,
                                }
                            );


                        if (!response.ok) {
                            throw new Error(
                                'Gagal mengambil invitation.'
                            );
                        }


                        const data =
                            await response.json();


                        /*
                         * Replace table HTML.
                         */
                        this.$refs
                            .results
                            .innerHTML =
                                data.html;


                        /*
                         * Update URL browser
                         * tanpa reload.
                         */
                        window.history
                            .replaceState(
                                {},
                                '',
                                url.pathname
                                + url.search
                            );


                    } catch (error) {

                        /*
                         * AbortError normal terjadi
                         * ketika user mengetik cepat.
                         */
                        if (
                            error.name
                            !== 'AbortError'
                        ) {

                            console.error(
                                'Invitation search error:',
                                error
                            );
                        }

                    } finally {

                        this.loading =
                            false;

                    }
                },


                /*
                 * Reset filter.
                 */
                reset() {

                    this.search = '';

                    this.status = '';

                    this.load();
                },


                /*
                 * AJAX pagination.
                 */
                handlePagination(event) {

                    const link =
                        event.target.closest(
                            'a'
                        );


                    if (!link) {
                        return;
                    }


                    /*
                     * Hanya intercept link pagination.
                     */
                    const pagination =
                        link.closest(
                            'nav'
                        );


                    if (!pagination) {
                        return;
                    }


                    const href =
                        link.getAttribute(
                            'href'
                        );


                    if (!href) {
                        return;
                    }


                    event.preventDefault();


                    this.load(
                        href
                    );


                    /*
                     * Scroll kembali ke filter/table.
                     */
                    this.$el
                        .scrollIntoView({
                            behavior:
                                'smooth',

                            block:
                                'start',
                        });
                },

            };

        };
</script>

</x-app-layout>
