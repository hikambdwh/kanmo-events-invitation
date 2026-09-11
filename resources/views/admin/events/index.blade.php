<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Events
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage event dan invitation QR.
                </p>
            </div>

            <a
                href="{{ route('admin.events.create') }}"
                class="rounded-lg bg-[#f94242] px-4 py-2.5
                       text-sm font-medium cursor-pointer text-white hover:bg-[#c53434]"
            >
                Create Event
            </a>

        </div>
    </x-slot>


    <div class="py-12">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if(session('success'))
                <div
                    class="px-4 py-3 mb-6 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50"
                >
                    {{ session('success') }}
                </div>
            @endif


            <div class="overflow-hidden bg-white shadow-sm rounded-xl">

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200">

                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase">
                                    Event
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase">
                                    Date
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-left text-gray-500 uppercase">
                                    QR Prefix
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                                    Invitations
                                </th>

                                <th class="px-6 py-4 text-xs font-semibold tracking-wider text-center text-gray-500 uppercase">
                                    Scanned
                                </th>

                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @forelse($events as $event)

                                <tr class="hover:bg-gray-50">

                                    <td class="px-6 py-5">

                                        <a
                                            href="{{ route('admin.events.show', $event) }}"
                                            class="font-medium text-gray-900 hover:underline"
                                        >
                                            {{ $event->name }}
                                        </a>

                                        @if($event->venue)
                                            <div class="mt-1 text-sm text-gray-500">
                                                {{ $event->venue }}
                                            </div>
                                        @endif

                                    </td>

                                    <td class="px-6 py-5 text-sm text-gray-600 whitespace-nowrap">
                                        {{ $event->event_date?->format('d M Y, H:i') ?? '-' }}
                                    </td>

                                    <td class="px-6 py-5">

                                        <span
                                            class="rounded-md bg-gray-100 px-2.5 py-1
                                                   font-mono text-xs text-gray-700"
                                        >
                                            {{ $event->qr_prefix }}
                                        </span>

                                    </td>

                                    <td class="px-6 py-5 text-sm text-center text-gray-700">
                                        {{ $event->invitations_count }}
                                    </td>

                                    <td class="px-6 py-5 text-sm text-center text-gray-700">
                                        {{ $event->checked_in_count }}
                                    </td>

                                    <td class="px-6 py-5 text-right">

                                        <a
                                            href="{{ route('admin.events.show', $event) }}"
                                            class="text-sm font-medium text-gray-700 hover:text-black"
                                        >
                                            View
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-6 py-16 text-center"
                                    >
                                        <p class="font-medium text-gray-700">
                                            Belum ada event.
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Buat event pertama untuk mulai generate QR.
                                        </p>

                                        <a
                                            href="{{ route('admin.events.create') }}"
                                            class="mt-5 inline-block rounded-lg bg-[#f94242] hover:bg-[#c53434]
                                                   px-4 py-2.5 text-sm cursor-pointer font-medium
                                                   text-white "
                                        >
                                            Create Event
                                        </a>
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            <div class="mt-6">
                {{ $events->links() }}
            </div>

        </div>
    </div>

</x-app-layout>