import type { Config } from 'tailwindcss'

export default {
  content: [
    './index.html',
    './src/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#f0f0ff',
          100: '#e5e3ff',
          200: '#cccaff',
          300: '#b0a9ff',
          400: '#9181fb',
          500: '#7c5cf7',
          600: '#667eea',   // primary
          700: '#764ba2',   // secondary
          800: '#5a3682',
          900: '#3e2163',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
      boxShadow: {
        card: '0 2px 16px rgba(0,0,0,0.08)',
        'card-hover': '0 8px 32px rgba(102, 126, 234, 0.2)',
      },
      screens: {
        // Mobile-first — max 430px treated as "app" mode
        app: { max: '430px' },
      },
    },
  },
  plugins: [],
} satisfies Config
