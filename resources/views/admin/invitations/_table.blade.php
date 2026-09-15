{{-- Table --}}

<div class="mt-6 overflow-hidden bg-white shadow-sm rounded-xl">

    <div class="overflow-x-auto">

        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Guest Code
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        QR
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        QR Value
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Status
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Limit
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Last Check In
                    </th>

                    <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody class="bg-white divide-y divide-gray-100">

                @forelse($invitations
                                as $invitation)
                    <tr class="hover:bg-gray-50">

                        {{-- Guest code --}}

                        <td class="px-6 py-5 whitespace-nowrap">

                            <span class="font-mono text-sm font-semibold text-gray-900">
                                {{ $invitation->guest_code }}
                            </span>

                        </td>


                        {{-- QR --}}

                        <td class="px-6 py-4">

                            <a href="{{ $invitation->getQrImageUrl(800) }}" target="_blank" rel="noopener">

                                <img src="{{ $invitation->getQrImageUrl(100) }}" alt="{{ $invitation->guest_code }}"
                                    class="p-1 bg-white border border-gray-200 rounded-lg size-20" loading="lazy">

                            </a>

                        </td>


                        {{-- QR value --}}

                        <td class="px-6 py-5">

                            <div class="max-w-xs font-mono text-xs text-gray-500 break-all">
                                {{ $invitation->qr_value }}
                            </div>

                        </td>


                        {{-- Status --}}

                        <td class="px-6 py-5">

                            @if ($invitation->is_checked_in)
                                <span
                                    class="inline-flex px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">
                                    Scanned
                                </span>
                            @else
                                <span
                                    class="inline-flex px-3 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded">
                                    Available
                                </span>
                            @endif

                        </td>

                        {{-- Scan Limit --}}
                        <td class="px-6 py-5 text-center">

                            <div class="inline-flex items-center gap-1 px-3 py-2 bg-gray-100 rounded-lg">

                                <span class="font-semibold text-gray-900">
                                    {{ $invitation->remaining_scans }}
                                </span>

                                <span class="text-gray-400">
                                    /
                                </span>

                                <span class="text-gray-500">
                                    {{ $invitation->scan_limit }}
                                </span>

                            </div>

                        </td>


                        {{-- Checked in --}}

                        <td class="px-6 py-5 text-sm text-gray-500 whitespace-nowrap">

                            @if ($invitation->checked_in_at)
                                {{ $invitation->checked_in_at->format('d M Y H:i:s') }}
                            @else
                                -
                            @endif

                        </td>

                        {{-- Action --}}
                        <td class="px-6 py-5 whitespace-nowrap">

                            <div x-data="{
                                showLimitModal: false
                            }">

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

                                    <button type="submit" @disabled($invitation->scan_count <= 0)
                                        class="w-full px-3 py-2 mx-auto text-xs font-medium text-white transition bg-red-600 border border-red-600 rounded-lg cursor-pointer hover:bg-red-500 disabled:cursor-not-allowed disabled:bg-white disabled:border-red-300 disabled:text-red-300 disabled:hover:bg-transparent">
                                        Reset Count
                                    </button>
                                </form>


                                {{-- Mark As Scanned --}}
                                <form method="POST"
                                    action="{{ route('admin.events.invitations.mark-scanned', [$event, $invitation]) }}"
                                    onsubmit="return confirm('Tandai {{ $invitation->guest_code }} sebagai scanned?')">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" @disabled($invitation->is_checked_in)
                                        class="w-full px-3 py-2 mt-1 text-xs font-medium text-white transition bg-green-600 border border-2 border-green-200 rounded-lg cursor-pointer hover:bg-green-500 disabled:cursor-not-allowed disabled:bg-white disabled:border-green-300 disabled:text-green-300 disabled:hover:bg-transparent">
                                        Mark as Scanned
                                    </button>
                                </form>

                                {{-- Set Limit --}}
                                <div>
                                    <button type="button" @click="showLimitModal = true"
                                        class="w-full px-3 py-2 mt-1 text-xs font-medium text-white transition bg-blue-600 border border-blue-600 rounded-lg cursor-pointer hover:bg-blue-500">
                                        Set Limit
                                    </button>
                                </div>

                                <template x-teleport="body">

                                    <div x-show="showLimitModal" x-cloak
                                        @keydown.escape.window="
            showLimitModal = false
        "
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4">

                                        {{-- Overlay --}}
                                        <div class="absolute inset-0 bg-black/50"
                                            @click="
                showLimitModal = false
            "></div>


                                        {{-- Modal --}}
                                        <div class="relative z-10 w-full max-w-sm p-6 bg-white shadow-xl rounded-2xl">

                                            <h3 class="text-lg font-semibold text-gray-900">
                                                Set Scan Limit
                                            </h3>

                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ $invitation->guest_code }}
                                            </p>


                                            <div class="p-3 mt-4 text-sm text-gray-600 rounded-lg bg-gray-50">
                                                Used:
                                                <strong>
                                                    {{ $invitation->scan_count }}
                                                </strong>

                                                <br>

                                                Current limit:
                                                <strong>
                                                    {{ $invitation->scan_limit }}
                                                </strong>
                                            </div>


                                            <form method="POST"
                                                action="{{ route('admin.events.invitations.set-limit', [$event, $invitation]) }}"
                                                class="mt-5">

                                                @csrf
                                                @method('PATCH')


                                                <label class="text-sm font-medium text-gray-700">
                                                    Scan Limit
                                                </label>


                                                <input type="number" name="scan_limit"
                                                    value="{{ $invitation->scan_limit }}"
                                                    min="{{ max(1, $invitation->scan_count) }}" max="1000" required
                                                    class="mt-2 w-full
                           rounded-lg
                           border-gray-300
                           focus:border-[#f94242]
                           focus:ring-[#f94242]">


                                                <p class="mt-2 text-xs text-gray-500">
                                                    Limit tidak boleh lebih kecil
                                                    dari jumlah scan yang sudah digunakan.
                                                </p>


                                                <div class="flex justify-end gap-2 mt-6">

                                                    <button type="button"
                                                        @click="
                            showLimitModal = false
                        "
                                                        class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                                                        Cancel
                                                    </button>


                                                    <button type="submit"
                                                        class="rounded-lg
                                                                bg-[#f94242]
                                                                px-4 py-2
                                                                text-sm font-medium
                                                                text-white
                                                                hover:bg-[#c53434]">
                                                        Save Limit
                                                    </button>

                                                </div>

                                            </form>

                                        </div>

                                    </div>

                                </template>

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="7" class="px-6 py-16 text-center">

                            <p class="font-medium text-gray-700">
                                Invitation tidak ditemukan.
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
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
