{{--
    Les notifications de la requete : un statut flashe par l'action precedente,
    ou le refus d'une regle de planning. Les statuts techniques de Breeze sont
    traduits ici ; tout autre statut est deja une phrase affichable.
--}}

@php
    $statusMessages = [
        'profile-updated' => 'Vos informations ont été enregistrées.',
    ];

    $status = session('status');
    $statusMessage = is_string($status) && $status !== 'verification-link-sent'
        ? ($statusMessages[$status] ?? $status)
        : null;
@endphp

<div id="toasts" class="pointer-events-none fixed inset-x-4 bottom-24 z-50 flex flex-col items-center gap-2 md:inset-x-auto md:bottom-6 md:right-6 md:w-96 print-hidden"
     aria-live="polite">
    @if ($statusMessage !== null)
        <x-ui.toast>{{ $statusMessage }}</x-ui.toast>
    @endif

    @error('shift')
        <x-ui.toast tone="danger" :duration="9000">{{ $message }}</x-ui.toast>
    @enderror
</div>
