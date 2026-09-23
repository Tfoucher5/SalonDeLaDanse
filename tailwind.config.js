import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // Les neutres passent par l'echelle `zinc` native : rien a declarer ici.
            colors: {
                // Accent unique : reserve a ce sur quoi on peut cliquer.
                primary: {
                    DEFAULT: '#4338CA',
                    hover: '#3730A3',
                    ring: '#4F46E5',
                    soft: '#EEF2FF',
                },
                // Jauges de remplissage : la couleur appartient a l'information.
                gauge: {
                    free: '#15803D',
                    tight: '#B45309',
                    full: '#71717A',
                },
                // Echec de validation et action destructrice, rien d'autre.
                danger: '#B91C1C',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            // Une seule ombre dans toute l'application, reservee a ce qui flotte
            // vraiment : menu deroulant et modale. Ailleurs, la bordure suffit.
            boxShadow: {
                overlay: '0 10px 30px -12px rgb(24 24 27 / 0.22), 0 2px 8px -4px rgb(24 24 27 / 0.10)',
            },
            // Cible tactile minimale du mobile first, nommee pour ne plus
            // avoir a se souvenir que 44 px valent `h-11`.
            minHeight: {
                touch: '2.75rem',
            },
            minWidth: {
                touch: '2.75rem',
            },
        },
    },

    plugins: [forms],
};
