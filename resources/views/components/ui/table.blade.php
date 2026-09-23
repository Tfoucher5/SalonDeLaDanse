{{--
    Tableau d'administration. Lignes separees par une bordure, pas de rayures
    alternees : la bordure se lit mieux en densite. Le defilement horizontal est
    porte par l'enveloppe, jamais par la page.
--}}

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-2xl bg-white shadow-card ring-1 ring-zinc-900/5']) }}>
    <table class="w-full text-left text-sm">
        @isset($head)
            <thead class="border-b border-zinc-200">
                <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:text-xs [&>th]:font-bold [&>th]:uppercase [&>th]:tracking-wider [&>th]:text-zinc-500">
                    {{ $head }}
                </tr>
            </thead>
        @endisset

        <tbody class="divide-y divide-zinc-200 [&>tr:hover]:bg-zinc-50 [&>tr>td]:px-4 [&>tr>td]:py-3 [&>tr>td]:text-zinc-900">
            {{ $slot }}
        </tbody>
    </table>
</div>
