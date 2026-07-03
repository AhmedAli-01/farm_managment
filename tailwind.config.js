/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                farm: {
                    green: {
                        light: '#4ade80',  // For highlights/badges
                        DEFAULT: '#16a34a',// Main brand green
                        dark: '#166534',   // Deep green for headers
                    },
                    blue: {
                        light: '#60a5fa',
                        DEFAULT: '#2563eb',// Shabelle River blue
                        dark: '#1e40af',
                    },
                    amber: {
                        light: '#fbbf24',
                        DEFAULT: '#d97706',// For tracking cash/expenses
                        dark: '#78350f',
                    },
                    slate: {
                        bg: '#f8fafc',     // Ultra-clean app background
                        text: '#0f172a',   // Crisp, dark text
                    }
                },
            },
            fontFamily: {
                sans: ['Figtree', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
    plugins: [],
};