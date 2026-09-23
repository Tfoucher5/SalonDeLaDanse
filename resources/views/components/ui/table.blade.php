{{--
    Tableau d'administration. Lignes separees par une bordure, pas de rayures
    alternees : la bordure se lit mieux en densite. Le defilement horizontal est
    porte par l'enveloppe, jamais par la page.
--}}

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-zinc-200 bg-white']) }}>
    <table class="w-full text-left text-sm">
        @isset($head)
            <thead class="border-b border-zinc-200">
                <tr class="[&>th]:px-4 [&>th]:py-3 [&>th]:text-sm [&>th]:font-medium [&>th]:text-zinc-500">
                    {{ $head }}
                </tr>
            </thead>
        @endisset

        <tbody class="divide-y divide-zinc-200 [&>tr:hover]:bg-zinc-100 [&>tr>td]:px-4 [&>tr>td]:py-3 [&>tr>td]:text-zinc-900">
            {{ $slot }}
        </tbody>
    </table>
</div>
