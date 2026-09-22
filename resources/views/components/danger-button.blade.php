<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center h-10 px-4 bg-white border border-zinc-200 rounded-md font-medium text-sm text-danger hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
