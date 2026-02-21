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
          600: '#667eea',  // primary
          700: '#764ba2',  // secondary
          800: '#5a3682',
          900: '#3e2163',
        },
        cta: {
          400: '#ff8e8e',
          500: '#ff6b6b',  // CTA primary
          600: '#ee5a24',  // CTA hover
        },
      },
      fontFamily: {
        sans:     ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
        heading:  ['Poppins', 'Inter', 'sans-serif'],
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
        '4xl': '2rem',
      },
      boxShadow: {
        card:        '0 2px 16px rgba(0,0,0,0.07)',
        'card-hover':'0 8px 32px rgba(102,126,234,0.18)',
        cta:         '0 4px 20px rgba(255,107,107,0.35)',
        brand:       '0 4px 20px rgba(102,126,234,0.35)',
      },
      backgroundImage: {
        'gradient-brand': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'gradient-cta':   'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)',
        'gradient-card':  'linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(0,0,0,0.6) 100%)',
      },
      screens: {
        // Standard breakpoints + custom for sidebar switch
        'sidebar': '1024px',
      },
      spacing: {
        'bottom-nav': '4.5rem',  // 72px — height of bottom tab bar
        'top-bar':    '3.5rem',  // 56px — height of top bar
        'sidebar-w':  '16rem',   // 256px — sidebar width
      },
    },
  },
  plugins: [],
} satisfies Config
