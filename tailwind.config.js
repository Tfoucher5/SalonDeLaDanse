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
            colors: {
                // Neutres chauds de la maquette « Salon Danse Élan » : l'echelle garde
                // le nom `zinc` pour que toutes les vues en heritent sans reecriture,
                // mais ses valeurs tirent vers la porcelaine et le prune.
                zinc: {
                    50: '#FAFAF7',
                    100: '#F4F0EC',
                    200: '#EAE3DE',
                    300: '#D8CFCA',
                    400: '#A59CA1',
                    500: '#716B70',
                    600: '#5A5258',
                    700: '#443C42',
                    800: '#2E272B',
                    900: '#1F1A1C',
                },
                // Terracotta Danse : l'action. Le DEFAULT est assombri pour tenir
                // le contraste AA du texte blanc ; `bright` est la teinte de marque.
                primary: {
                    DEFAULT: '#B93A24',
                    hover: '#9A2C19',
                    bright: '#E0533C',
                    ring: '#E0533C',
                    soft: '#FDEBE7',
                },
                // Prune Velours : etats, etiquettes de section, profondeur.
                plum: {
                    DEFAULT: '#6C2E58',
                    soft: '#F6E8F1',
                },
                // Jauges de remplissage : la couleur appartient a l'information.
                gauge: {
                    free: '#0F766E',
                    tight: '#B45309',
                    full: '#716B70',
                },
                // Echec de validation et action destructrice, rien d'autre.
                danger: '#B91C1C',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            // Ombres teintees prune : chaleur sans grisaille. `overlay` reste
            // reservee a ce qui flotte (menu deroulant, modale).
            boxShadow: {
                card: '0 4px 20px -2px rgb(108 46 88 / 0.06), 0 1px 3px 0 rgb(0 0 0 / 0.03)',
                lift: '0 12px 28px -4px rgb(224 83 60 / 0.14), 0 4px 10px -2px rgb(0 0 0 / 0.04)',
                cta: '0 6px 16px -4px rgb(185 58 36 / 0.45)',
                overlay: '0 10px 30px -12px rgb(31 26 28 / 0.22), 0 2px 8px -4px rgb(31 26 28 / 0.10)',
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
