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
                        class="rounded-lg bg-gray-900
                   px-4 py-2.5
                   text-sm font-medium
                   text-white
                   hover:bg-black">
                        Generate
                    </button>
                </form>


                <button x-data type="button" @click="$dispatch('open-download-modal')"
                    class="rounded-lg border border-gray-300 bg-white
           px-4 py-2.5 text-sm font-medium text-gray-700
           hover:bg-gray-50">
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

                <form method="GET" action="{{ route('admin.events.invitations.index', $event) }}"
                    class="flex flex-col gap-3
                           sm:flex-row">

                    <div class="flex-1">

                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Search guest00001..."
                            class="w-full rounded-lg
                                   border-gray-300
                                   text-sm
                                   focus:border-gray-900
                                   focus:ring-gray-900">

                    </div>


                    <div>

                        <select name="status"
                            class="w-full rounded-lg
                                   border-gray-300
                                   text-sm
                                   sm:w-44
                                   focus:border-gray-900
                                   focus:ring-gray-900">

                            <option value="">
                                All Status
                            </option>

                            <option value="available" @selected($status === 'available')>
                                Available
                            </option>

                            <option value="checked_in" @selected($status === 'checked_in')>
                                Checked In
                            </option>

                        </select>

                    </div>


                    <button type="submit"
                        class="rounded-lg
                               bg-gray-900
                               px-5 py-2.5
                               text-sm
                               font-medium
                               text-white
                               hover:bg-black">
                        Filter
                    </button>


                    @if ($search || $status)
                        <a href="{{ route('admin.events.invitations.index', $event) }}"
                            class="rounded-lg
                                   border
                                   border-gray-300
                                   px-5 py-2.5
                                   text-center
                                   text-sm
                                   font-medium
                                   text-gray-700
                                   hover:bg-gray-50">
                            Reset
                        </a>
                    @endif

                </form>

            </div>


            {{-- Table --}}

            <div
                class="mt-6 overflow-hidden
                       rounded-xl bg-white
                       shadow-sm">

                <div class="overflow-x-auto">

                    <table
                        class="min-w-full
                               divide-y
                               divide-gray-200">

                        <thead class="bg-gray-50">

                            <tr>

                                <th
                                    class="px-6 py-4
                                           text-center
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500">
                                    Guest Code
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-center
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500">
                                    QR
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-center
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500">
                                    QR Value
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-center
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500">
                                    Status
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-center
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500">
                                    Check In
                                </th>

                                <th
                                    class="px-6 py-4
                                            text-center
                                            text-xs
                                            font-semibold
                                            uppercase
                                            tracking-wider
                                            text-gray-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody
                            class="divide-y
                                   divide-gray-100
                                   bg-white">

                            @forelse($invitations
                                as $invitation)
                                <tr class="hover:bg-gray-50">

                                    {{-- Guest code --}}

                                    <td
                                        class="whitespace-nowrap
                                               px-6 py-5">

                                        <span
                                            class="font-mono
                                                   text-sm
                                                   font-semibold
                                                   text-gray-900">
                                            {{ $invitation->guest_code }}
                                        </span>

                                    </td>


                                    {{-- QR --}}

                                    <td class="px-6 py-4">

                                        <a href="{{ $invitation->getQrImageUrl(800) }}" target="_blank" rel="noopener">

                                            <img src="{{ $invitation->getQrImageUrl(100) }}"
                                                alt="{{ $invitation->guest_code }}"
                                                class="size-20
                                                       rounded-lg
                                                       border
                                                       border-gray-200
                                                       bg-white
                                                       p-1"
                                                loading="lazy">

                                        </a>

                                    </td>


                                    {{-- QR value --}}

                                    <td class="px-6 py-5">

                                        <div
                                            class="max-w-xs
                                                   break-all
                                                   font-mono
                                                   text-xs
                                                   text-gray-500">
                                            {{ $invitation->qr_value }}
                                        </div>

                                    </td>


                                    {{-- Status --}}

                                    <td class="px-6 py-5">

                                        @if ($invitation->is_checked_in)
                                            <span
                                                class="inline-flex
                                                       rounded-full
                                                       bg-green-100
                                                       px-3 py-1
                                                       text-xs
                                                       font-medium
                                                       text-green-700">
                                                Scanned
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex
                                                       rounded
                                                       bg-yellow-100
                                                       px-3 py-1
                                                       text-xs
                                                       text-yellow-800
                                                       font-medium">
                                                Available
                                            </span>
                                        @endif

                                    </td>


                                    {{-- Checked in --}}

                                    <td
                                        class="whitespace-nowrap
                                               px-6 py-5
                                               text-sm
                                               text-gray-500">

                                        @if ($invitation->checked_in_at)
                                            {{ $invitation->checked_in_at->format('d M Y H:i:s') }}
                                        @else
                                            -
                                        @endif

                                    </td>

                                    {{-- Action --}}
                                    <td class="whitespace-nowrap px-6 py-5">

                                        <div class="flex items-center gap-2">

                                            {{-- Reset --}}
                                            <form method="POST"
                                                action="{{ route('admin.events.invitations.reset', [$event, $invitation]) }}"
                                                onsubmit="
                return confirm(
                    'Reset {{ $invitation->guest_code }}? QR ini akan dapat digunakan kembali.'
                )
            ">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" @disabled(!$invitation->is_checked_in)
                                                    class="rounded-lg
                       border border-red-200
                       px-3 py-2
                       cursor-pointer
                       text-xs font-medium
                       text-red-600
                       transition
                       hover:bg-red-50
                       disabled:cursor-not-allowed
                       disabled:border-gray-200
                       disabled:text-gray-300
                       disabled:hover:bg-transparent">
                                                    Reset
                                                </button>
                                            </form>


                                            {{-- Mark As Scanned --}}
                                            <form method="POST"
                                                action="{{ route('admin.events.invitations.mark-scanned', [$event, $invitation]) }}"
                                                onsubmit="
                return confirm(
                    'Tandai {{ $invitation->guest_code }} sebagai scanned?'
                )
            ">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" @disabled($invitation->is_checked_in)
                                                    class="rounded-lg border-2
                                                        border border-green-200
                                                        px-3 py-2
                                                        text-xs font-medium
                                                        text-green-600
                                                        cursor-pointer
                                                        transition
                                                        hover:bg-green-50
                                                        disabled:cursor-not-allowed
                                                        disabled:border-green-700
                                                        disabled:bg-green-700
                                                        disabled:text-white">
                                                    Mark as Scanned
                                                </button>
                                            </form>

                                        </div>

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td colspan="5"
                                        class="px-6 py-16
                                               text-center">

                                        <p
                                            class="font-medium
                                                   text-gray-700">
                                            Invitation tidak ditemukan.
                                        </p>

                                        <p
                                            class="mt-1
                                                   text-sm
                                                   text-gray-500">
                                            Generate invitation
                                            atau ubah filter.
                                        </p>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Pagination --}}

            <div class="mt-6">

                {{ $invitations->links() }}

            </div>

        </div>

        <div x-data="qrExport({
            startUrl: '{{ route('admin.events.exports.qr.start', $event) }}',
            csrf: '{{ csrf_token() }}'
        })" @open-download-modal.window="downloadModal = true" x-show="downloadModal" x-cloak
            @keydown.escape.window="downloadModal = false"
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
                        class="flex w-full
           items-center
           justify-between
           rounded-xl
           border border-gray-200
           p-5
           text-left
           transition
           hover:border-gray-900
           hover:bg-gray-50
           disabled:cursor-wait
           disabled:opacity-60">
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
                                Download seluruh QR
                                dalam satu file ZIP.
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
                           text-gray-500"
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
                           bg-gray-900
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
                                    class="rounded-lg
                       bg-gray-900
                       px-4 py-2.5
                       text-sm
                       font-medium
                       text-white
                       hover:bg-black">
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
                        class="flex w-full
           items-center
           justify-between
           rounded-xl
           border
           border-gray-200
           p-5
           text-left
           transition
           hover:border-gray-900
           hover:bg-gray-50">
                        <div>

                            <p class="font-medium
                   text-gray-900">
                                With Design
                            </p>

                            <p class="mt-1 text-sm
                   text-gray-500">
                                Apply QR ke invitation card
                                sebelum export.
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

</x-app-layout>
