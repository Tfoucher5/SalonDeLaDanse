@props([
    'title' => null,
    'width' => 'sm:max-w-md',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layout.head :title="$title" />
    </head>

    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <main class="w-full {{ $width }}">
                <div class="mb-6 flex justify-center">
                    <x-ui.brand :href="url('/')" size="lg" />
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 sm:p-8">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-sm text-zinc-500">
                    {{ config('app.name') }} — JayDance Fam
                </p>
            </main>
        </div>
    </body>
</html>
