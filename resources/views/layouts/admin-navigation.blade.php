{{--
    Navigation du back-office. Elle ne ressemble pas a celle du benevole : la
    marque porte « Administration », et un lien ramene toujours vers l'espace
    benevole pour qu'on sache de quel cote de l'outil on se trouve.
--}}

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-zinc-900/5 bg-white/90 backdrop-blur print-hidden">
    <x-ui.container size="xl">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-8">
                <x-ui.brand :href="route('admin.dashboard')" tagline="Administration" class="min-w-0" />

                <div class="hidden items-center gap-8 sm:flex">
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        Vue d'ensemble
                    </x-nav-link>

                    <x-nav-link :href="route('admin.planning')" :active="request()->routeIs('admin.planning')">
                        Planning
                    </x-nav-link>

                    <x-nav-link :href="route('admin.volunteers.index')" :active="request()->routeIs('admin.volunteers.*')">
                        Bénévoles
                    </x-nav-link>

                    <x-nav-link :href="route('admin.missions.index')" :active="request()->routeIs('admin.missions.*')">
                        Missions
                    </x-nav-link>

                    <x-nav-link :href="route('admin.exports.index')" :active="request()->routeIs('admin.exports.*')">
                        Exports
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex h-11 min-h-touch items-center gap-2 rounded-xl py-1 pe-2 ps-1 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-100">
                            <x-ui.avatar :user="Auth::user()" />
                            <span class="max-w-[12rem] truncate">{{ Auth::user()->full_name }}</span>

                            <svg class="h-4 w-4 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-zinc-900/5 px-4 py-3">
                            <p class="truncate text-sm font-medium text-zinc-900">{{ Auth::user()->full_name }}</p>
                            <p class="truncate text-sm text-zinc-500">{{ Auth::user()->role->label() }}</p>
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

            <button type="button" @click="open = ! open"
                    :aria-expanded="open"
                    aria-controls="menu-admin-mobile"
                    class="inline-flex h-11 w-11 min-h-touch min-w-touch items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 sm:hidden">
                <span class="sr-only">Ouvrir le menu</span>

                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </x-ui.container>

    <div id="menu-admin-mobile" x-show="open" x-cloak class="border-t border-zinc-900/5 sm:hidden">
        <div class="py-2">
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                Vue d'ensemble
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('admin.planning')" :active="request()->routeIs('admin.planning')">
                Planning
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('admin.volunteers.index')" :active="request()->routeIs('admin.volunteers.*')">
                Bénévoles
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('admin.missions.index')" :active="request()->routeIs('admin.missions.*')">
                Missions
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('admin.exports.index')" :active="request()->routeIs('admin.exports.*')">
                Exports
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-zinc-900/5 py-2">
            <div class="flex items-center gap-3 px-4 py-3">
                <x-ui.avatar :user="Auth::user()" size="h-10 w-10" />

                <div class="min-w-0">
                    <p class="truncate font-medium text-zinc-900">{{ Auth::user()->full_name }}</p>
                    <p class="truncate text-sm text-zinc-500">{{ Auth::user()->role->label() }}</p>
                </div>
            </div>

            <x-responsive-nav-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-responsive-nav-link>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
