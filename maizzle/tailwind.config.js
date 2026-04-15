/** @type {import('tailwindcss').Config} */
module.exports = {
  presets: [
    require('tailwindcss-preset-email'),
  ],
  theme: {
    extend: {
      colors: {
        'brand-primary': {
          light: '#d02e3a',
          DEFAULT: '#c02434',
          dark: '#931e2d',
          content: '#fff',
        },
        'brand-secondary': {
          light: '#3652bf',
          DEFAULT: '#304296',
          dark: '#2d3c7b',
          content: '#ffffff',
        },
      },
    },
  },
  content: [
    './components/**/*.html',
    './emails/**/*.html',
    './layouts/**/*.html',
  ],
}
