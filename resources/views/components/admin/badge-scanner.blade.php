{{--
    La fenetre de scan des badges, posee une fois par le layout du back-office
    et ouverte depuis n'importe quel ecran par
    `$dispatch('open-modal', 'badge-scanner')`.

    La camera lit le QR code, le verdict s'affiche ici sans recharger la page :
    photo, nom, identifiant, et ou la personne est attendue aujourd'hui. Sans
    camera — refusee, ou page servie en HTTP —, l'identifiant imprime sur le
    badge se saisit a la main. Controle visuel seulement : rien n'est
    enregistre. Voir `resources/js/badge-scanner.js`.
--}}

<x-modal name="badge-scanner" maxWidth="md">
    <div x-data="badgeScanner(@js(route('admin.badges.scan')))" class="p-5 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-zinc-900">Scanner un badge</h2>
                <p class="mt-0.5 text-sm text-zinc-500">Visez le QR code en bas à droite du badge.</p>
            </div>

            <button type="button" x-on:click="show = false"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900">
                <span class="sr-only">Fermer</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.3 4.3a1 1 0 011.4 0L10 8.6l4.3-4.3a1 1 0 111.4 1.4L11.4 10l4.3 4.3a1 1 0 01-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 01-1.4-1.4L8.6 10 4.3 5.7a1 1 0 010-1.4z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        {{-- La camera. Elle reste dans le DOM pendant le verdict : le lecteur
             reprend sur le meme element au badge suivant. --}}
        <div x-show="cameraReady && phase !== 'result'"
             class="relative mt-4 aspect-square overflow-hidden rounded-2xl bg-zinc-900">
            <video x-ref="video" class="h-full w-full object-cover" muted playsinline></video>

            <div x-show="phase === 'starting' || phase === 'checking'" x-cloak
                 class="absolute inset-0 flex items-center justify-center bg-zinc-900/60 text-sm font-semibold text-white">
                <span x-text="phase === 'starting' ? 'Ouverture de la caméra…' : 'Vérification…'"></span>
            </div>
        </div>

        <div x-show="phase === 'starting' && ! cameraReady" x-cloak
             class="mt-4 flex aspect-square items-center justify-center rounded-2xl bg-zinc-100 text-sm font-semibold text-zinc-500">
            Ouverture de la caméra…
        </div>

        <template x-if="cameraError !== '' && phase !== 'result'">
            <div role="status" class="mt-4 flex items-start gap-3 rounded-xl bg-gauge-tight/10 p-4 text-sm text-zinc-900">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-gauge-tight" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.1 3.3a1 1 0 011.8 0l6.5 12.2a1 1 0 01-.9 1.5H3.5a1 1 0 01-.9-1.5L9.1 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 102 0V8a1 1 0 00-1-1zm0 7.5a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
                <p x-text="cameraError"></p>
            </div>
        </template>

        {{-- Le verdict. Le vert n'est reserve qu'au badge valable : tout le
             reste refuse l'entree, en rouge. --}}
        <div x-show="phase === 'result'" x-cloak aria-live="assertive" class="mt-4">
            <template x-if="result">
                <div>
                    <div class="flex items-start gap-3 rounded-2xl p-4"
                         x-bind:class="isValid ? 'bg-gauge-free/10 ring-1 ring-inset ring-gauge-free/30' : 'bg-danger/10 ring-1 ring-inset ring-danger/30'">
                        <svg x-show="isValid" class="h-7 w-7 shrink-0 text-gauge-free" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm4 5.7l-5 5a1 1 0 01-1.4 0l-2.3-2.3a1 1 0 011.4-1.4l1.6 1.6 4.3-4.3a1 1 0 011.4 1.4z" />
                        </svg>
                        <svg x-show="! isValid" class="h-7 w-7 shrink-0 text-danger" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zM7.7 6.3a1 1 0 00-1.4 1.4L8.6 10l-2.3 2.3a1 1 0 101.4 1.4l2.3-2.3 2.3 2.3a1 1 0 001.4-1.4L11.4 10l2.3-2.3a1 1 0 00-1.4-1.4L10 8.6 7.7 6.3z" />
                        </svg>

                        <div class="min-w-0">
                            <p class="text-lg font-extrabold tracking-tight" x-bind:class="isValid ? 'text-gauge-free' : 'text-danger'" x-text="result.title"></p>
                            <p class="text-sm text-zinc-900" x-text="result.message"></p>
                        </div>
                    </div>

                    <template x-if="result.volunteer">
                        <div class="mt-4">
                            <div class="flex items-center gap-4">
                                <template x-if="result.volunteer.photo">
                                    <img x-bind:src="result.volunteer.photo" alt="" class="h-20 w-20 shrink-0 rounded-full object-cover ring-2 ring-white">
                                </template>
                                <template x-if="! result.volunteer.photo">
                                    <span class="inline-flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-primary text-2xl font-bold text-white" x-text="result.volunteer.initials"></span>
                                </template>

                                <div class="min-w-0">
                                    <p class="truncate text-xl font-extrabold tracking-tight text-zinc-900" x-text="result.volunteer.name"></p>
                                    <p class="text-sm text-zinc-500 tabular-grid">
                                        <span x-text="result.volunteer.identifier"></span>
                                        <span x-show="result.volunteer.edition"> · <span x-text="result.volunteer.edition"></span></span>
                                    </p>
                                </div>
                            </div>

                            <p class="mt-4 text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-zinc-500">Planning</p>

                            <p x-show="result.volunteer.shifts.length === 0" class="mt-2 rounded-xl bg-zinc-50 px-3 py-2.5 text-sm text-zinc-500">Aucun créneau retenu.</p>

                            <ul class="mt-2 max-h-48 space-y-2 overflow-y-auto">
                                <template x-for="shift in result.volunteer.shifts" :key="shift.day + shift.time + shift.mission">
                                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl p-3 text-sm tabular-grid"
                                        x-bind:class="shift.today ? 'bg-primary-soft ring-1 ring-primary/30' : 'bg-zinc-50'">
                                        <span class="font-bold text-zinc-900" x-text="shift.day + ' · ' + shift.time"></span>
                                        <span class="min-w-0 flex-1 text-zinc-600" x-text="shift.mission"></span>
                                        <span x-show="shift.today" class="inline-flex h-7 items-center gap-1.5 rounded-full bg-primary-soft px-3 text-xs font-semibold text-primary">
                                            <span class="h-1.5 w-1.5 rounded-full bg-primary" aria-hidden="true"></span>
                                            Aujourd'hui
                                        </span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row">
                        <template x-if="result.volunteer">
                            <x-ui.button x-bind:href="result.volunteer.url" href="#" size="touch" class="sm:flex-1">Ouvrir la fiche</x-ui.button>
                        </template>

                        <x-ui.button type="button" variant="primary" size="touch" x-on:click="next()" class="sm:flex-1">
                            Badge suivant
                        </x-ui.button>
                    </div>
                </div>
            </template>
        </div>

        {{-- La saisie de repli, toujours disponible : un badge abime ou une
             camera refusee ne bloquent pas l'entree. --}}
        <form x-show="phase !== 'result'" x-on:submit.prevent="submitManual()" class="mt-4 flex gap-2">
            <label for="badge-scanner-code" class="sr-only">Identifiant du badge</label>
            <x-text-input id="badge-scanner-code" x-model="manualCode" placeholder="SDD27-0042" autocomplete="off" class="min-w-0 flex-1 uppercase" />
            <x-ui.button type="submit" x-bind:disabled="manualCode.trim() === '' || phase === 'checking'">Vérifier</x-ui.button>
        </form>
    </div>
</x-modal>
