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
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Official Gakkum Colors (Deep Forest Teal)
                primary: {
                    DEFAULT: '#015850',
                    50: '#f0f9f8',
                    100: '#dcefee',
                    200: '#bee0dc',
                    300: '#94cac3',
                    400: '#64aca4',
                    500: '#469188',
                    600: '#34756e',
                    700: '#2c5e59',
                    800: '#264d49',
                    900: '#23403d',
                    950: '#0f2624',
                },
                // Prestigious Institutional Accent (Warm Amber / Gold)
                accent: {
                    DEFAULT: '#d97706',
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                // Neutral & Institutional Secondary
                secondary: {
                    DEFAULT: '#475569',
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                // Clean neutral surfaces for institutional look
                surface: {
                    50: '#f8fafc',  // slate-50 equivalent
                    100: '#f1f5f9', // slate-100 equivalent
                    200: '#e2e8f0', // slate-200
                    300: '#cbd5e1', // slate-300
                    subtle: '#fcfdfd',
                    DEFAULT: '#ffffff',
                },
                // Dark grays for strong typography hierarchy
                on: {
                    surface: '#0f172a', // slate-900 (Headings, primary text)
                    muted: '#475569',   // slate-600 (Body, secondary text)
                    subtle: '#94a3b8',  // slate-400 (Placeholders, tertiary)
                },
                // Refined semantic feedback colors
                danger: {
                    DEFAULT: '#dc2626',
                    hover: '#b91c1c',
                    light: '#fef2f2',
                    border: '#fecaca',
                    dark: '#7f1d1d',
                },
                warning: {
                    DEFAULT: '#d97706',
                    hover: '#b45309',
                    light: '#fffbeb',
                    border: '#fde68a',
                    dark: '#78350f',
                },
                success: {
                    DEFAULT: '#059669',
                    hover: '#047857',
                    light: '#ecfdf5',
                    border: '#a7f3d0',
                    dark: '#064e3b',
                },
                info: {
                    DEFAULT: '#2563eb',
                    hover: '#1d4ed8',
                    light: '#eff6ff',
                    border: '#bfdbfe',
                    dark: '#1e3a8a',
                },
            },
            borderRadius: {
                // Sharper corners for a more serious/official look
                none: '0',
                sm: '0.125rem',
                DEFAULT: '0.25rem', // 4px
                md: '0.375rem',     // 6px
                lg: '0.5rem',       // 8px
                xl: '0.75rem',      // 12px (Max for cards)
                '2xl': '1rem',      // 16px
                full: '9999px',
            },
            boxShadow: {
                // Crisp, subtle layered shadows (institutional, not blurry/bloated)
                'xs': '0 1px 2px 0 rgb(0 0 0 / 0.04)',
                'sm': '0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06)',
                DEFAULT: '0 2px 5px -1px rgb(0 0 0 / 0.08), 0 1px 3px -1px rgb(0 0 0 / 0.06)',
                'md': '0 6px 12px -2px rgb(0 0 0 / 0.08), 0 3px 6px -3px rgb(0 0 0 / 0.06)',
                'lg': '0 12px 24px -4px rgb(0 0 0 / 0.09), 0 6px 12px -4px rgb(0 0 0 / 0.05)',
                'glow-primary': '0 0 16px -2px rgba(1, 88, 80, 0.25)',
                'glow-accent': '0 0 16px -2px rgba(217, 119, 6, 0.25)',
            }
        },
    },
    plugins: [forms],
};
