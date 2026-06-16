import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Noto Sans Thai', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                gray: {
                    750: '#2d3748',
                }
            }
        },
    },

    plugins: [forms],
};
