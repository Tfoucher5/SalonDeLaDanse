<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center h-10 px-4 bg-primary border border-transparent rounded-md font-medium text-sm text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary-ring focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
