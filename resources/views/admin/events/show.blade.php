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
                    class="rounded-lg bg-[#f94242] hover:bg-[#c53434] px-4 py-2.5 text-sm font-medium cursor-pointer text-white">
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
                    <ul class="pl-5 space-y-1 list-disc">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


            {{-- Statistics --}}
            <div class="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
                
                {{-- Total QR --}}
        
                <div class="p-5 bg-white shadow-sm rounded-xl">

                    <p class="text-sm text-gray-500">
                        Total QR
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($statistics['total_qr']) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Generated QR codes
                    </p>

                </div>

                {{-- Available QR --}}
                <div class="p-5 bg-white shadow-sm rounded-xl">

                    <p class="text-sm text-gray-500">
                        Available QR
                    </p>

                    <p class="mt-2 text-2xl font-semibold text-gray-900">
                        {{ number_format($statistics['available_qr']) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        QR codes that can still be scanned
                    </p>

                </div>

            </div>


            <div class="grid gap-6 mt-6 lg:grid-cols-3">

                {{-- Event Information --}}
                <div class="p-6 bg-white shadow-sm rounded-xl">

                    <h3 class="font-semibold text-gray-900">
                        Event Information
                    </h3>

                    <dl class="mt-6 space-y-5">

                        <div>
                            <dt class="text-xs tracking-wide text-gray-400 uppercase">
                                Event Date
                            </dt>

                            <dd class="mt-1 text-sm text-gray-800">
                                {{ $event->event_date?->format('d M Y, H:i') ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs tracking-wide text-gray-400 uppercase">
                                Venue
                            </dt>

                            <dd class="mt-1 text-sm text-gray-800">
                                {{ $event->venue ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs tracking-wide text-gray-400 uppercase">
                                Address
                            </dt>

                            <dd class="mt-1 text-sm text-gray-800 whitespace-pre-line">
                                {{ $event->address ?? '-' }}
                            </dd>
                        </div>


                        <div>
                            <dt class="text-xs tracking-wide text-gray-400 uppercase">
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

                    <div class="overflow-hidden bg-white shadow-sm rounded-xl">

                        <div
                            class="flex items-center justify-between px-6 py-5 border-b border-gray-100">

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
                                        class="flex items-center justify-between gap-4 px-6 py-4">

                                        <div>
                                            <p
                                                class="font-mono text-sm font-medium text-gray-900">
                                                {{ $invitation->guest_code }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                {{ $invitation->qr_token }}
                                            </p>
                                        </div>


                                        @if ($invitation->checked_in_at)
                                            <div class="text-right">

                                                <span
                                                    class="px-3 py-1 text-xs font-medium text-green-700 rounded-full bg-green-50">
                                                    Scanned
                                                </span>

                                                <p class="mt-1 text-xs text-gray-400">
                                                    {{ $invitation->checked_in_at->format('H:i:s') }}
                                                </p>

                                            </div>
                                        @else
                                            <span
                                                class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded-full">
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
            <div class="p-6 mt-8 bg-white border border-red-200 rounded-xl">

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
