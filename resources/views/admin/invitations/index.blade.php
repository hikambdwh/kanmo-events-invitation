<x-app-layout>

    <x-slot name="header">

        <div
            class="flex flex-col gap-4
                   sm:flex-row
                   sm:items-center
                   sm:justify-between"
        >

            <div>

                <a
                    href="{{ route(
                        'admin.events.show',
                        $event
                    ) }}"
                    class="text-sm text-gray-500
                           hover:text-gray-900"
                >
                    ← {{ $event->name }}
                </a>

                <h2
                    class="mt-1 text-xl
                           font-semibold
                           text-gray-900"
                >
                    Invitations
                </h2>

            </div>


            <form
                method="POST"
                action="{{ route(
                    'admin.events.invitations.generate',
                    $event
                ) }}"
                class="flex items-center gap-2"
            >

                @csrf

                <input
                    type="number"
                    name="quantity"
                    min="1"
                    max="1000"
                    value="10"
                    class="w-24 rounded-lg
                           border-gray-300
                           text-sm
                           focus:border-gray-900
                           focus:ring-gray-900"
                    required
                >

                <button
                    type="submit"
                    class="rounded-lg
                           bg-gray-900
                           px-4 py-2.5
                           text-sm font-medium
                           text-white
                           hover:bg-black"
                >
                    Generate
                </button>

            </form>

        </div>

    </x-slot>


    <div class="py-12">

        <div
            class="mx-auto max-w-7xl
                   px-4 sm:px-6 lg:px-8"
        >

            {{-- Notification --}}

            @if(session('success'))

                <div
                    class="mb-6 rounded-lg
                           border border-green-200
                           bg-green-50
                           px-4 py-3
                           text-sm text-green-700"
                >
                    {{ session('success') }}
                </div>

            @endif


            @if($errors->any())

                <div
                    class="mb-6 rounded-lg
                           border border-red-200
                           bg-red-50
                           px-4 py-3
                           text-sm text-red-700"
                >

                    <ul
                        class="list-disc
                               space-y-1 pl-5"
                    >

                        @foreach(
                            $errors->all()
                            as $error
                        )

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- Statistics --}}

            <div
                class="grid gap-4
                       sm:grid-cols-3"
            >

                <div
                    class="rounded-xl bg-white
                           p-5 shadow-sm"
                >

                    <p
                        class="text-sm
                               text-gray-500"
                    >
                        Total Invitations
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900"
                    >
                        {{
                            number_format(
                                $statistics['total']
                            )
                        }}
                    </p>

                </div>


                <div
                    class="rounded-xl bg-white
                           p-5 shadow-sm"
                >

                    <p
                        class="text-sm
                               text-gray-500"
                    >
                        Available
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900"
                    >
                        {{
                            number_format(
                                $statistics[
                                    'available'
                                ]
                            )
                        }}
                    </p>

                </div>


                <div
                    class="rounded-xl bg-white
                           p-5 shadow-sm"
                >

                    <p
                        class="text-sm
                               text-gray-500"
                    >
                        Checked In
                    </p>

                    <p
                        class="mt-2 text-2xl
                               font-semibold
                               text-gray-900"
                    >
                        {{
                            number_format(
                                $statistics[
                                    'checked_in'
                                ]
                            )
                        }}
                    </p>

                </div>

            </div>


            {{-- Filter --}}

            <div
                class="mt-6 rounded-xl
                       bg-white p-5
                       shadow-sm"
            >

                <form
                    method="GET"
                    action="{{ route(
                        'admin.events.invitations.index',
                        $event
                    ) }}"
                    class="flex flex-col gap-3
                           sm:flex-row"
                >

                    <div class="flex-1">

                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Search guest00001..."
                            class="w-full rounded-lg
                                   border-gray-300
                                   text-sm
                                   focus:border-gray-900
                                   focus:ring-gray-900"
                        >

                    </div>


                    <div>

                        <select
                            name="status"
                            class="w-full rounded-lg
                                   border-gray-300
                                   text-sm
                                   sm:w-44
                                   focus:border-gray-900
                                   focus:ring-gray-900"
                        >

                            <option value="">
                                All Status
                            </option>

                            <option
                                value="available"
                                @selected(
                                    $status ===
                                    'available'
                                )
                            >
                                Available
                            </option>

                            <option
                                value="checked_in"
                                @selected(
                                    $status ===
                                    'checked_in'
                                )
                            >
                                Checked In
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="rounded-lg
                               bg-gray-900
                               px-5 py-2.5
                               text-sm
                               font-medium
                               text-white
                               hover:bg-black"
                    >
                        Filter
                    </button>


                    @if($search || $status)

                        <a
                            href="{{ route(
                                'admin.events.invitations.index',
                                $event
                            ) }}"
                            class="rounded-lg
                                   border
                                   border-gray-300
                                   px-5 py-2.5
                                   text-center
                                   text-sm
                                   font-medium
                                   text-gray-700
                                   hover:bg-gray-50"
                        >
                            Reset
                        </a>

                    @endif

                </form>

            </div>


            {{-- Table --}}

            <div
                class="mt-6 overflow-hidden
                       rounded-xl bg-white
                       shadow-sm"
            >

                <div class="overflow-x-auto">

                    <table
                        class="min-w-full
                               divide-y
                               divide-gray-200"
                    >

                        <thead class="bg-gray-50">

                            <tr>

                                <th
                                    class="px-6 py-4
                                           text-left
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500"
                                >
                                    Guest Code
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-left
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500"
                                >
                                    QR
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-left
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500"
                                >
                                    QR Value
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-left
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500"
                                >
                                    Status
                                </th>

                                <th
                                    class="px-6 py-4
                                           text-left
                                           text-xs
                                           font-semibold
                                           uppercase
                                           tracking-wider
                                           text-gray-500"
                                >
                                    Check In
                                </th>

                            </tr>

                        </thead>


                        <tbody
                            class="divide-y
                                   divide-gray-100
                                   bg-white"
                        >

                            @forelse(
                                $invitations
                                as $invitation
                            )

                                <tr
                                    class="hover:bg-gray-50"
                                >

                                    {{-- Guest code --}}

                                    <td
                                        class="whitespace-nowrap
                                               px-6 py-5"
                                    >

                                        <span
                                            class="font-mono
                                                   text-sm
                                                   font-semibold
                                                   text-gray-900"
                                        >
                                            {{
                                                $invitation
                                                    ->guest_code
                                            }}
                                        </span>

                                    </td>


                                    {{-- QR --}}

                                    <td
                                        class="px-6 py-4"
                                    >

                                        <a
                                            href="{{
                                                $invitation
                                                    ->getQrImageUrl(
                                                        800
                                                    )
                                            }}"
                                            target="_blank"
                                            rel="noopener"
                                        >

                                            <img
                                                src="{{
                                                    $invitation
                                                        ->getQrImageUrl(
                                                            100
                                                        )
                                                }}"
                                                alt="{{
                                                    $invitation
                                                        ->guest_code
                                                }}"
                                                class="size-20
                                                       rounded-lg
                                                       border
                                                       border-gray-200
                                                       bg-white
                                                       p-1"
                                                loading="lazy"
                                            >

                                        </a>

                                    </td>


                                    {{-- QR value --}}

                                    <td
                                        class="px-6 py-5"
                                    >

                                        <div
                                            class="max-w-xs
                                                   break-all
                                                   font-mono
                                                   text-xs
                                                   text-gray-500"
                                        >
                                            {{
                                                $invitation
                                                    ->qr_value
                                            }}
                                        </div>

                                    </td>


                                    {{-- Status --}}

                                    <td
                                        class="px-6 py-5"
                                    >

                                        @if(
                                            $invitation
                                                ->is_checked_in
                                        )

                                            <span
                                                class="inline-flex
                                                       rounded-full
                                                       bg-green-50
                                                       px-3 py-1
                                                       text-xs
                                                       font-medium
                                                       text-green-700"
                                            >
                                                Checked In
                                            </span>

                                        @else

                                            <span
                                                class="inline-flex
                                                       rounded-full
                                                       bg-gray-100
                                                       px-3 py-1
                                                       text-xs
                                                       font-medium
                                                       text-gray-600"
                                            >
                                                Available
                                            </span>

                                        @endif

                                    </td>


                                    {{-- Checked in --}}

                                    <td
                                        class="whitespace-nowrap
                                               px-6 py-5
                                               text-sm
                                               text-gray-500"
                                    >

                                        @if(
                                            $invitation
                                                ->checked_in_at
                                        )

                                            {{
                                                $invitation
                                                    ->checked_in_at
                                                    ->format(
                                                        'd M Y H:i:s'
                                                    )
                                            }}

                                        @else

                                            -

                                        @endif

                                    </td>

                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        class="px-6 py-16
                                               text-center"
                                    >

                                        <p
                                            class="font-medium
                                                   text-gray-700"
                                        >
                                            Invitation tidak ditemukan.
                                        </p>

                                        <p
                                            class="mt-1
                                                   text-sm
                                                   text-gray-500"
                                        >
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

                {{
                    $invitations
                        ->links()
                }}

            </div>

        </div>

    </div>

</x-app-layout>