import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * "eat" cartoon design system.
 *
 * A vivid, playful, sticker-book aesthetic: chunky rounded shapes, a thick
 * "ink" outline, and hard offset shadows instead of soft blurs. The tokens
 * below are the single source of truth — pair them with the component classes
 * defined in resources/css/app.css (.btn, .card, .input, .badge, …).
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    // The design-system showcase composes brand utilities dynamically
    // (e.g. `bg-${name}-${shade}`), so keep those tokens from being purged.
    safelist: [
        { pattern: /^bg-(punch|sunny|mint|grape|berry|sky)-(200|300|400|500|600)$/ },
        { pattern: /^shadow-cartoon(-xs|-sm|-lg|-xl)?$/ },
    ],

    theme: {
        extend: {
            fontFamily: {
                // Rounded, chunky display face for headings, buttons & badges.
                display: ['Fredoka', ...defaultTheme.fontFamily.sans],
                // Friendly, legible body face.
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                // The outline / text colour every cartoon shape is drawn with.
                ink: {
                    DEFAULT: '#221f3b',
                    soft: '#4a4463',
                    muted: '#6b647f',
                    900: '#17142a',
                    800: '#221f3b',
                    700: '#3a3357',
                },
                // Warm paper background.
                cream: {
                    DEFAULT: '#fff8ef',
                    50: '#fffdf9',
                    100: '#fff8ef',
                    200: '#fff1dd',
                    300: '#ffe6c4',
                },
                // Primary action — appetising coral / tangerine.
                punch: {
                    50: '#fff1ec',
                    100: '#ffe0d6',
                    200: '#ffc2af',
                    300: '#ff9e80',
                    400: '#ff7a54',
                    500: '#ff5a2c',
                    600: '#f0410f',
                    700: '#c7330b',
                    800: '#9e2a0c',
                    900: '#7f260f',
                },
                // Sunny highlight yellow.
                sunny: {
                    100: '#fff6d6',
                    200: '#ffec9e',
                    300: '#ffde5c',
                    400: '#ffd23d',
                    500: '#fbbf24',
                    600: '#e5a50a',
                },
                // Fresh mint / teal — success & calm accents.
                mint: {
                    100: '#d6fbef',
                    200: '#a5f3dc',
                    300: '#6ee7c4',
                    400: '#34d8ae',
                    500: '#14c39a',
                    600: '#0ba382',
                },
                // Juicy grape — focus rings & links.
                grape: {
                    100: '#ece7ff',
                    200: '#d3c8ff',
                    300: '#b9a5ff',
                    400: '#9b82ff',
                    500: '#7c5cfc',
                    600: '#6338f5',
                    700: '#4f27d0',
                },
                // Bubblegum berry — playful danger / love.
                berry: {
                    100: '#ffe0ec',
                    200: '#ffbdd4',
                    300: '#ff90b4',
                    400: '#ff5c8a',
                    500: '#ff4d85',
                    600: '#ed2e6b',
                    700: '#c71a52',
                },
                // Bright sky — informational accents.
                sky: {
                    100: '#dcf3ff',
                    200: '#b3e6ff',
                    300: '#7dd4fb',
                    400: '#56c7fb',
                    500: '#22abf0',
                    600: '#0e8fd4',
                },
            },

            borderRadius: {
                xl2: '1.25rem',
                blob: '2rem',
            },

            borderWidth: {
                3: '3px',
            },

            // Hard, offset "sticker" shadows drawn in ink — no blur.
            boxShadow: {
                'cartoon-xs': '2px 2px 0 0 rgb(34 31 59)',
                'cartoon-sm': '3px 3px 0 0 rgb(34 31 59)',
                cartoon: '4px 4px 0 0 rgb(34 31 59)',
                'cartoon-lg': '6px 6px 0 0 rgb(34 31 59)',
                'cartoon-xl': '8px 8px 0 0 rgb(34 31 59)',
            },

            keyframes: {
                wiggle: {
                    '0%, 100%': { transform: 'rotate(-3deg)' },
                    '50%': { transform: 'rotate(3deg)' },
                },
                'pop-in': {
                    '0%': { transform: 'scale(0.9)', opacity: '0' },
                    '60%': { transform: 'scale(1.03)', opacity: '1' },
                    '100%': { transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-6px)' },
                },
            },

            animation: {
                wiggle: 'wiggle 0.4s ease-in-out',
                'pop-in': 'pop-in 0.25s ease-out both',
                float: 'float 3s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
