{{-- Table --}}

<div class="mt-6 overflow-hidden bg-white shadow-sm rounded-xl">

    <div class="overflow-x-auto">

        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">

                <tr>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Guest Code
                    </th>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        QR
                    </th>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        QR Value
                    </th>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Status
                    </th>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Check In
                    </th>

                    <th
                        class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody
                class="bg-white divide-y divide-gray-100">

                @forelse($invitations
                                as $invitation)
                    <tr class="hover:bg-gray-50">

                        {{-- Guest code --}}

                        <td class="px-6 py-5 whitespace-nowrap">

                            <span
                                class="font-mono text-sm font-semibold text-gray-900">
                                {{ $invitation->guest_code }}
                            </span>

                        </td>


                        {{-- QR --}}

                        <td class="px-6 py-4">

                            <a href="{{ $invitation->getQrImageUrl(800) }}" target="_blank" rel="noopener">

                                <img src="{{ $invitation->getQrImageUrl(100) }}" alt="{{ $invitation->guest_code }}"
                                    class="p-1 bg-white border border-gray-200 rounded-lg size-20"
                                    loading="lazy">

                            </a>

                        </td>


                        {{-- QR value --}}

                        <td class="px-6 py-5">

                            <div
                                class="max-w-xs font-mono text-xs text-gray-500 break-all">
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


                        {{-- Checked in --}}

                        <td
                            class="px-6 py-5 text-sm text-gray-500 whitespace-nowrap">

                            @if ($invitation->checked_in_at)
                                {{ $invitation->checked_in_at->format('d M Y H:i:s') }}
                            @else
                                -
                            @endif

                        </td>

                        {{-- Action --}}
                        <td class="px-6 py-5 whitespace-nowrap">

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
                                        class="px-3 py-2 text-xs font-medium text-red-600 transition border border-red-200 rounded-lg cursor-pointer hover:bg-red-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-300 disabled:hover:bg-transparent">
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
                                        class="px-3 py-2 text-xs font-medium text-green-600 transition border border-2 border-green-200 rounded-lg cursor-pointer hover:bg-green-50 disabled:cursor-not-allowed disabled:border-green-700 disabled:bg-green-700 disabled:text-white">
                                        Mark as Scanned
                                    </button>
                                </form>

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td colspan="6"
                            class="px-6 py-16 text-center">

                            <p class="font-medium text-gray-700">
                                Invitation tidak ditemukan.
                            </p>

                            <p
                                class="mt-1 text-sm text-gray-500">
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
