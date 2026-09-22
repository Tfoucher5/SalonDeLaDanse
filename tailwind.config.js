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
        },
    },

    plugins: [forms],
};
