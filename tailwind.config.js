/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./assets/css/**/*.css",
    "./src/css/**/*.css",
    "./blocks/**/*.{php,js,jsx,ts,tsx}",
    "./src/**/*.{js,jsx,ts,tsx}",
    "./assets/js/**/*.js",
    "./parts/**/*.php",
    "./templates/**/*.php",
    "!./node_modules",
  ],
  theme: {
    container: {
      center: true,
      padding: {
        DEFAULT: "1rem",
        lg: "2rem",
        xl: "2rem",
        "2xl": "2rem",
      },
    },
    screens: {
      sm: "640px",
      md: "768px",
      lg: "1024px",
      xl: "1280px",
      "2xl": "1600px",
      "3xl": "1921px",
    },
    extend: {
      colors: {
        green: {
          deep: "#29472d",
          accent1: "#d1d436",
          accent2: "#e2ef4a",
        },
        brand: {
          10: "#f7f3f2",
          20: "#efe8e6",
          30: "#dad3cf",
          40: "#b7b0ab",
          50: "#707070",
          black: "#292725",
        },
      },
      fontFamily: {
        hanken: [
          "Hanken Grotesk",
          "system-ui",
          "Segoe UI",
          "Arial",
          "sans-serif",
        ],
        museum: ["PP Museum", "system-ui", "Segoe UI", "Arial", "sans-serif"],
      },
      fontSize: {
        // Body text variants
        "body-lg": "clamp(24px, 1.5vw + 20px, 27px)",
        "body-sm": "clamp(14px, 0.8vw + 12px, 15px)",
        // Heading sizes
        h1: "clamp(44px, 3vw + 32px, 56px)",
        h2: "clamp(28px, 2vw + 24px, 36px)",
        h3: "clamp(22px, 1.4vw + 18px, 26px)",
        h4: "clamp(18px, 1.2vw + 16px, 20px)",
        h5: "clamp(16px, 1vw + 14px, 18px)",
        h6: "clamp(14px, 0.8vw + 12px, 15px)",
      },
      lineHeight: {
        heading: "1.2",
        body: "1.6",
      },
      borderWidth: {
        hairline: "0.5px",
      },
      letterSpacing: {
        tightish: "-0.01em",
        tighter2: "-0.02em",
      },
    },
  },
  plugins: [],
};
