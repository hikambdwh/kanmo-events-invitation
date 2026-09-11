<x-app-layout>

    <x-slot name="header">
        <div class="flex items-center justify-between">

            <h2 class="text-xl font-semibold text-gray-800">
                Create Event
            </h2>

            <a
                href="{{ route('admin.events.index') }}"
                class="text-sm font-medium text-gray-600 hover:text-gray-900"
            >
                Back
            </a>

        </div>
    </x-slot>


    <div class="py-12">
        <div class="max-w-3xl px-4 mx-auto sm:px-6 lg:px-8">

            <div class="p-6 bg-white shadow-sm rounded-xl sm:p-8">

                <form
                    method="POST"
                    action="{{ route('admin.events.store') }}"
                >

                    @csrf

                    @include('admin.events.partials.form')

                    <div class="flex justify-end gap-3 mt-8">

                        <a
                            href="{{ route('admin.events.index') }}"
                            class="px-5 py-3 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-[#f94242] cursor-pointer hover:bg-[#c53434] px-5 py-3
                                   text-sm font-medium text-white"
                        >
                            Create Event
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>

</x-app-layout>