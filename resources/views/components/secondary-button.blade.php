<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center h-10 px-4 bg-white border border-zinc-200 rounded-md font-medium text-sm text-zinc-900 hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-primary-ring focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
