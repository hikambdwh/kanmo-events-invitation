{{-- Table --}}

<div class="mt-6 overflow-hidden
                       rounded-xl bg-white
                       shadow-sm">

    <div class="overflow-x-auto">

        <table class="min-w-full
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

                        <td class="whitespace-nowrap
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

                                <img src="{{ $invitation->getQrImageUrl(100) }}" alt="{{ $invitation->guest_code }}"
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

                        <td colspan="6"
                            class="px-6 py-16
                                               text-center">

                            <p class="font-medium
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
