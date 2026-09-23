{{--
    Liste deroulante. Meme trait, meme hauteur et meme anneau de focus qu'un
    champ texte (`x-text-input`).

    Le panneau d'options est dessine par `resources/js/select-menu.js`, par
    dessus un <select> natif conserve : les attributs (`name`, `id`,
    `required`, `x-on:change`…) vont a ce <select>, `class` a l'enveloppe.
    Sans JavaScript, le <select> natif s'affiche tel quel.
--}}

@props(['disabled' => false])

<div x-data="selectMenu"
     x-on:click.outside="open = false"
     x-on:keydown.escape="open = false"
     {{ $attributes->only('class')->merge(['class' => 'relative w-full']) }}>
    <select x-ref="native"
            @disabled($disabled)
            x-bind:class="ready && 'sr-only'"
            x-on:focus="focused = true"
            x-on:blur="focused = false"
            {{ $attributes->except('class')->merge(['class' => 'h-12 w-full rounded-xl border-zinc-900/15 bg-white pe-10 ps-4 text-[0.9375rem] text-zinc-900 transition focus:border-primary-bright focus:ring-4 focus:ring-primary-bright/15 disabled:bg-zinc-50 disabled:text-zinc-400']) }}>
        {{ $slot }}
    </select>

    {{-- Pour la souris et le doigt ; le clavier passe par le <select> natif. --}}
    <button type="button"
            x-show="ready"
            x-cloak
            tabindex="-1"
            aria-hidden="true"
            @disabled($disabled)
            x-on:click="toggle()"
            x-bind:class="(open || focused) ? 'border-primary-bright ring-4 ring-primary-bright/15' : 'border-zinc-900/15 hover:border-zinc-900/30'"
            class="flex h-12 w-full items-center justify-between gap-3 rounded-xl border bg-white pe-3 ps-4 text-start text-[0.9375rem] transition disabled:cursor-not-allowed disabled:bg-zinc-50 disabled:text-zinc-400">
        <span class="min-w-0 truncate" x-bind:class="value === '' ? 'text-zinc-500' : 'font-medium text-zinc-900'" x-text="label"></span>

        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 transition" x-bind:class="open && 'rotate-180 bg-primary-soft text-primary'">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.3 7.3a1 1 0 011.4 0L10 10.6l3.3-3.3a1 1 0 111.4 1.4l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 010-1.4z" clip-rule="evenodd" />
            </svg>
        </span>
    </button>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="-translate-y-1 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-end="opacity-0"
         aria-hidden="true"
         class="absolute inset-x-0 z-50 mt-2 max-h-72 min-w-[14rem] overflow-y-auto rounded-2xl bg-white p-1.5 ring-1 ring-zinc-900/5 shadow-overlay">
        <template x-for="(group, groupIndex) in groups" x-bind:key="groupIndex">
            <div class="[&+div]:mt-1 [&+div]:border-t [&+div]:border-zinc-200 [&+div]:pt-1">
                <p x-show="group.label" x-text="group.label"
                   class="px-3 pb-1 pt-2 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-400"></p>

                <template x-for="option in group.options" x-bind:key="option.value">
                    <button type="button"
                            tabindex="-1"
                            x-on:click="choose(option.value)"
                            x-bind:disabled="option.disabled"
                            x-bind:class="option.value === value ? 'bg-primary-soft font-semibold text-primary' : 'text-zinc-900 hover:bg-zinc-100'"
                            class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-start text-sm transition disabled:opacity-40">
                        <span x-text="option.label"></span>

                        <svg x-show="option.value === value" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </template>
            </div>
        </template>
    </div>
</div>
