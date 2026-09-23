{{--
    Navigation principale.

    Mobile first : en haut, la marque et le compte ; en bas, une barre d'onglets
    fixe a portee de pouce. Au-dela de `md`, les onglets remontent au centre de
    la barre du haut, dans une capsule.
--}}

@php
    $sections = [
        ['label' => 'Tableau de bord', 'short' => 'Accueil', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard'),
            'icon' => 'M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6h-6v6H4a1 1 0 01-1-1v-9.5z'],
        ['label' => 'Réservation de créneaux', 'short' => 'Réservation', 'route' => 'planning.index', 'active' => request()->routeIs('planning.index'),
            'icon' => 'M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1zm3 8h3v3H8v-3z'],
        ['label' => 'Mon planning', 'short' => 'Mon planning', 'route' => 'planning.summary', 'active' => request()->routeIs('planning.summary'),
            'icon' => 'M9 12h6m-6 4h6M7 3h7l5 5v12a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1zm7 0v5h5'],
        ['label' => 'Mon profil', 'short' => 'Profil', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*'),
            'icon' => 'M12 12a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0'],
    ];
@endphp

<nav class="vt-site-nav sticky top-0 z-40 border-b border-zinc-900/5 bg-white/85 backdrop-blur-md print-hidden" aria-label="Navigation principale">
    <x-ui.container size="lg">
        <div class="flex h-16 items-center justify-between gap-4">
            <x-ui.brand :href="route('dashboard')" class="min-w-0" />

            <div class="hidden items-center gap-1 rounded-full bg-zinc-100 p-1 md:flex">
                @foreach ($sections as $section)
                    <x-nav-link :href="route($section['route'])" :active="$section['active']">
                        {{ $section['label'] }}
                    </x-nav-link>
                @endforeach
            </div>

            {{-- Compte : le menu deroulant est le seul element flottant de l'ecran. --}}
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button type="button" class="inline-flex h-11 min-h-touch items-center gap-2 rounded-xl py-1 pe-2 ps-1 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100">
                        <x-ui.avatar :user="Auth::user()" size="h-9 w-9" />
                        <span class="hidden max-w-[10rem] truncate lg:inline">{{ Auth::user()->full_name }}</span>
                        <span class="sr-only lg:hidden">Mon compte</span>

                        <svg class="h-4 w-4 fill-current text-zinc-500" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="border-b border-zinc-200 px-4 py-3">
                        <p class="truncate text-sm font-semibold text-zinc-900">{{ Auth::user()->full_name }}</p>
                        <p class="truncate text-sm text-zinc-500">{{ Auth::user()->email }}</p>
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
    </x-ui.container>
</nav>

{{-- Barre d'onglets mobile : quatre destinations, cibles tactiles de 56 px. --}}
<nav class="vt-tab-bar fixed inset-x-0 bottom-0 z-40 border-t border-zinc-900/5 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md md:hidden print-hidden"
     aria-label="Navigation mobile">
    <div class="mx-auto grid max-w-md grid-cols-4 gap-1 px-2 py-1.5">
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
