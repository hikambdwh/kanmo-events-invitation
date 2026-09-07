<div class="space-y-6">

    {{-- Event Name --}}
    <div>
        <label
            for="name"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            Event Name
        </label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $event->name ?? '') }}"
            placeholder="Roger Vivier Invitation"
            class="w-full rounded-lg border border-gray-300 px-4 py-3
                   text-gray-900 outline-none transition
                   focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
            required
        >

        @error('name')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- QR Prefix --}}
    <div>
        <label
            for="qr_prefix"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            QR Prefix
        </label>

        <input
            type="text"
            id="qr_prefix"
            name="qr_prefix"
            value="{{ old('qr_prefix', $event->qr_prefix ?? '') }}"
            placeholder="RogerVivier"
            class="w-full rounded-lg border border-gray-300 px-4 py-3
                   text-gray-900 outline-none transition
                   focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
            required
        >

        <p class="mt-2 text-sm text-gray-500">
            Digunakan sebagai prefix QR.
            Contoh:
            <span class="font-medium">
                RogerVivier:TOKEN
            </span>
        </p>

        @error('qr_prefix')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Event Date --}}
    <div>
        <label
            for="event_date"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            Event Date
        </label>

        <input
            type="datetime-local"
            id="event_date"
            name="event_date"
            value="{{
                old(
                    'event_date',
                    isset($event) && $event->event_date
                        ? $event->event_date->format('Y-m-d\TH:i')
                        : ''
                )
            }}"
            class="w-full rounded-lg border border-gray-300 px-4 py-3
                   text-gray-900 outline-none transition
                   focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
        >

        @error('event_date')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Venue --}}
    <div>
        <label
            for="venue"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            Venue
        </label>

        <input
            type="text"
            id="venue"
            name="venue"
            value="{{ old('venue', $event->venue ?? '') }}"
            placeholder="Plaza Indonesia"
            class="w-full rounded-lg border border-gray-300 px-4 py-3
                   text-gray-900 outline-none transition
                   focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
        >

        @error('venue')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Address --}}
    <div>
        <label
            for="address"
            class="mb-2 block text-sm font-medium text-gray-700"
        >
            Address
        </label>

        <textarea
            id="address"
            name="address"
            rows="4"
            placeholder="Alamat event..."
            class="w-full rounded-lg border border-gray-300 px-4 py-3
                   text-gray-900 outline-none transition
                   focus:border-gray-900 focus:ring-1 focus:ring-gray-900"
        >{{ old('address', $event->address ?? '') }}</textarea>

        @error('address')
            <p class="mt-2 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

</div>