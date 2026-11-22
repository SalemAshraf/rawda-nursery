import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                navy: {
                    50: '#f0f5fa',
                    100: '#dce7f2',
                    700: '#1d4368',
                    800: '#16334f',
                    900: '#0f2740',
                },
                mint: {
                    100: '#d9f7e8',
                    400: '#4ade9e',
                    500: '#2fd687',
                    600: '#1fbe70',
                },
            },
        },
    },

    plugins: [forms],
};
