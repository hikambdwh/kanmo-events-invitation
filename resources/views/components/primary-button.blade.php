<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#f94242] cursor-pointer border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#c53434] focus:bg-gray-700 active:bg-gray-900 focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
