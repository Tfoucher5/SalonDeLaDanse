{{--
    Navigation du back-office, construite comme celle du benevole : la marque a
    gauche, les onglets centres dans une capsule, le compte a droite. Sur
    mobile, les onglets descendent dans une barre fixe a portee de pouce, et le
    menu du compte ne garde que ce qu'il garde cote benevole.

    La marque porte « Administration » : on doit savoir en permanence de quel
    cote de l'outil on se trouve.
--}}

@php
    $sections = [
        ['label' => "Vue d'ensemble", 'short' => 'Accueil', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'),
            'icon' => 'M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1v-9.5z'],
        ['label' => 'Planning', 'short' => 'Planning', 'route' => 'admin.planning', 'active' => request()->routeIs('admin.planning'),
            'icon' => 'M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3 8h3v3H8v-3z'],
        ['label' => 'Bénévoles', 'short' => 'Bénévoles', 'route' => 'admin.volunteers.index', 'active' => request()->routeIs('admin.volunteers.*'),
            'icon' => 'M9 11a4 4 0 100-8 4 4 0 000 8zm-6 10a6 6 0 0112 0m2-10a3 3 0 100-6m4 16a5 5 0 00-4-4.9'],
        ['label' => 'Missions', 'short' => 'Missions', 'route' => 'admin.missions.index', 'active' => request()->routeIs('admin.missions.*'),
            'icon' => 'M9 5h6M9 3h6a1 1 0 011 1v1h2a1 1 0 011 1v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6a1 1 0 011-1h2V4a1 1 0 011-1zm0 10l2 2 4-4'],
        ['label' => 'Exports', 'short' => 'Exports', 'route' => 'admin.exports.index', 'active' => request()->routeIs('admin.exports.*'),
            'icon' => 'M12 4v11m0 0l-4-4m4 4l4-4M5 20h14'],
    ];
@endphp

<nav class="vt-site-nav sticky top-0 z-40 border-b border-zinc-900/5 bg-white/85 backdrop-blur-md print-hidden" aria-label="Navigation du back-office">
    <x-ui.container size="xl">
        <div class="grid h-16 grid-cols-[1fr_auto] items-center gap-4 md:grid-cols-[1fr_auto_1fr]">
            <x-ui.brand :href="route('admin.dashboard')" tagline="Administration" class="min-w-0" />

            <div class="hidden items-center gap-1 rounded-full bg-zinc-100 p-1 md:flex">
                @foreach ($sections as $section)
                    <x-nav-link :href="route($section['route'])" :active="$section['active']">
                        {{ $section['label'] }}
                    </x-nav-link>
                @endforeach
            </div>

            <div class="flex justify-end">
                <x-dropdown align="right" width="w-60">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex h-11 min-h-touch items-center gap-2 rounded-xl py-1 pe-2 ps-1 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100">
                            <x-ui.avatar :user="Auth::user()" size="h-9 w-9" />
                            <span class="hidden max-w-[10rem] truncate xl:inline">{{ Auth::user()->full_name }}</span>
                            <span class="sr-only xl:hidden">Mon compte</span>

                            <svg class="h-4 w-4 fill-current text-zinc-500" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="mb-1 flex items-center gap-3 border-b border-zinc-200 px-2.5 pb-3 pt-2">
                            <x-ui.avatar :user="Auth::user()" size="h-10 w-10" />

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-zinc-900">{{ Auth::user()->full_name }}</p>
                                <p class="truncate text-sm text-zinc-500">{{ Auth::user()->email }}</p>
                            </div>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </x-ui.container>
</nav>

{{-- Barre d'onglets mobile : cinq destinations, cibles tactiles de 56 px. --}}
<nav class="vt-tab-bar fixed inset-x-0 bottom-0 z-40 border-t border-zinc-900/5 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md md:hidden print-hidden"
     aria-label="Navigation mobile du back-office">
    <div class="mx-auto grid max-w-lg grid-cols-5 gap-1 px-2 py-1.5">
        @foreach ($sections as $section)
            <a href="{{ route($section['route']) }}"
               @if ($section['active']) aria-current="page" @endif
               @class([
                   'flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl text-[0.6875rem] font-semibold transition',
                   'bg-primary-soft text-primary' => $section['active'],
                   'text-zinc-500 hover:text-zinc-900' => ! $section['active'],
               ])>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="{{ $section['icon'] }}" />
                </svg>
                {{ $section['short'] }}
            </a>
        @endforeach
    </div>
</nav>
