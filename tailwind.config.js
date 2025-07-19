/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.php",
    "./app/views/**/*.{php,phtml,html}",
    "./app/public/**/*.{js,jsx}",
  ],
  theme: {
    extend: {
      colors: {
        orange: {
          500: "oklch(70.5% 0.213 47.604)",
          600: "oklch(64.6% 0.222 41.116)",
        },
        amber: {
          900: "oklch(41.4% 0.112 45.904)",
        },
      },
    },
  },
  plugins: [],
};
