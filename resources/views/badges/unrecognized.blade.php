{{--
    Un QR code dont la signature ne correspond pas : adresse retouchee, badge
    contrefait ou lien recopie de travers. On ne dit rien du compte vise.
--}}

<x-guest-layout title="Badge non reconnu">
    <div class="text-center">
        <x-ui.badge tone="danger" dot>Badge non reconnu</x-ui.badge>

        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-zinc-900">Ce badge n'a pas été émis par le Salon</h1>
        <p class="mt-2 text-sm text-zinc-500">
            Le code scanné ne correspond à aucun badge bénévole. Ne laissez pas passer sur la foi de ce badge,
            et prévenez l'équipe organisatrice.
        </p>
    </div>
</x-guest-layout>
