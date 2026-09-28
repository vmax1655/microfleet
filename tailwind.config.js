/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                primary: {
                    50: '#EFFAF7',
                    100: '#DBF3ED',
                    200: '#B4E5DA',
                    300: '#7FCFBE',
                    400: '#3FB39B',
                    500: '#17957F',
                    600: '#0F7A68',
                    700: '#0D6355',
                    800: '#0A4D42',
                    900: '#063831',
                    950: '#04231F',
                },
                accent: {
                    100: '#FEF0D6',
                    300: '#FAC978',
                    400: '#F5AC3D',
                    500: '#E8940F',
                    600: '#C2760B',
                    700: '#9A5B08',
                },
                neutral: {
                    50: '#F7FAF9',
                    100: '#F0F4F2',
                    200: '#E2E8E5',
                    300: '#C9D2CD',
                    400: '#A3AFA9',
                    500: '#7B8A84',
                    600: '#5A6B64',
                    700: '#3A4A44',
                    800: '#24322D',
                    900: '#16211D',
                    950: '#0E1512',
                },
                success: '#14804A',
                warning: '#B54708',
                danger: '#B42318',
                info: '#1570EF',
            },
            boxShadow: {
                card: '0 1px 2px rgba(14,21,18,.04), 0 1px 3px rgba(14,21,18,.06)',
                flyout: '0 10px 15px -3px rgba(14,21,18,.10), 0 4px 6px -4px rgba(14,21,18,.10)',
            },
            maxWidth: {
                content: '1440px',
            },
            transitionDuration: {
                200: '200ms',
            },
        },
    },
    plugins: [],
};
