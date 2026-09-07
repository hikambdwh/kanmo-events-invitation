<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">

            <div>
                <a href="{{ route('admin.events.index') }}" class="text-sm text-gray-500 hover:text-gray-900">
                    ← Events
                </a>

                <h2 class="mt-1 text-xl font-semibold text-gray-900">
                    {{ $event->name }}
                </h2>
            </div>

            <div class="flex gap-3">

                <a href="{{ route('admin.events.invitations.index', $event) }}"
                    class="rounded-lg
               bg-gray-900
               px-4 py-2.5
               text-sm
               font-medium
               text-white
               hover:bg-black">
                    Manage Invitations
                </a>

                <a href="{{ route('admin.events.edit', $event) }}"
                    class="rounded-lg
               border border-gray-300
               px-4 py-2.5
               text-sm
               font-medium
               text-gray-700
               hover:bg-gray-50">
                    Edit
                </a>

            </div>

        </div>
    </x-slot>


    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div
                    class="mb-6 rounded-lg border border-green-200
                           bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-6 rounded-lg border border-red-200
               bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- Statistics --}}
            <div class="grid gap-4 md:grid-cols-3">

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">
                        Total Invitations
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-gray-900">
                        {{ $event->invitations_count }}
                    </p>
                </div>


                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">
                        Available
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-gray-900">
                        {{ $event->invitations_count - $event->checked_in_count }}
                    </p>
                </div>


                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-500">
                        Checked In
                    </p>

                    <p class="mt-2 text-3xl font-semibold text-gray-900">
                        {{ $event->checked_in_count }}
                    </p>
                </div>

            </div>


            <div class="mt-6 grid gap-6 lg:grid-cols-3">

                {{-- Event Information --}}
                <div class="rounded-xl bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-gray-900">
                        Event Information
                    </h3>

                    <dl class="mt-6 space-y-5">

                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400">
                                Event Date
                            </dt>

                            <dd class="mt-1 text-sm text-gray-800">
                                {{ $event->event_date?->format('d M Y, H:i') ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400">
                                Venue
                            </dt>

                            <dd class="mt-1 text-sm text-gray-800">
                                {{ $event->venue ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400">
                                Address
                            </dt>

                            <dd class="mt-1 whitespace-pre-line text-sm text-gray-800">
                                {{ $event->address ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400">
                                QR Prefix
                            </dt>

                            <dd class="mt-1">
                                <span
                                    class="rounded-md bg-gray-100 px-2.5 py-1
                                           font-mono text-sm text-gray-800">
                                    {{ $event->qr_prefix }}
                                </span>
                            </dd>
                        </div>

                    </dl>

                </div>


                {{-- Invitations --}}
                <div class="lg:col-span-2">

                    <div class="overflow-hidden rounded-xl bg-white shadow-sm">

                        <div
                            class="flex items-center justify-between border-b
                                    border-gray-100 px-6 py-5">

                            <h3 class="font-semibold text-gray-900">
                                Recent Invitations
                            </h3>

                        </div>


                        @if ($event->invitations->isEmpty())

                            <div class="px-6 py-16 text-center">

                                <p class="font-medium text-gray-700">
                                    Belum ada invitation.
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    Generate QR invitation untuk event ini.
                                </p>

                            </div>
                        @else
                            <div class="divide-y divide-gray-100">

                                @foreach ($event->invitations as $invitation)
                                    <div
                                        class="flex items-center justify-between
                                                gap-4 px-6 py-4">

                                        <div>
                                            <p
                                                class="font-mono text-sm font-medium
                                                      text-gray-900">
                                                {{ $invitation->guest_code }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                {{ $invitation->qr_token }}
                                            </p>
                                        </div>


                                        @if ($invitation->checked_in_at)
                                            <div class="text-right">

                                                <span
                                                    class="rounded-full bg-green-50
                                                           px-3 py-1 text-xs font-medium
                                                           text-green-700">
                                                    Checked In
                                                </span>

                                                <p class="mt-1 text-xs text-gray-400">
                                                    {{ $invitation->checked_in_at->format('H:i:s') }}
                                                </p>

                                            </div>
                                        @else
                                            <span
                                                class="rounded-full bg-gray-100
                                                       px-3 py-1 text-xs font-medium
                                                       text-gray-600">
                                                Available
                                            </span>
                                        @endif

                                    </div>
                                @endforeach

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Danger Zone --}}
            <div class="mt-8 rounded-xl border border-red-200 bg-white p-6">

                <h3 class="font-semibold text-red-700">
                    Danger Zone
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Menghapus event juga akan menghapus seluruh invitation
                    yang terkait dengan event ini.
                </p>

                <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-5"
                    onsubmit="return confirm(
                        'Apakah kamu yakin ingin menghapus event ini?'
                    )">

                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="rounded-lg border border-red-300 px-4 py-2.5
                               text-sm font-medium text-red-700
                               hover:bg-red-50">
                        Delete Event
                    </button>

                </form>

            </div>

        </div>
    </div>

</x-app-layout>
