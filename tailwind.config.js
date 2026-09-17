import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: '#015850',
                dark: '#1E1935',
                info: '#0A7DEF',
                support: '#739ABB',
                brand: {
                    primary: '#015850', // Forest Teal
                    primaryLight: '#017A6F',
                    primaryDark: '#013D38',
                    secondary: '#F77A04', // Warm Amber
                    secondaryLight: '#F99B41',
                    secondaryDark: '#C76102',
                    informative: '#0A7DEF', // Cerulean
                },
            },
        },
    },

    plugins: [forms],
};
