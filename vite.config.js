import { defineConfig } from 'vite';
import liveReload from 'vite-plugin-live-reload';
import path from 'path';

export default defineConfig({
  plugins: [
    liveReload([
      // Watch PHP files for changes and trigger browser reload
      __dirname + '/**/*.php',
    ])
  ],

  // Root directory where Vite looks for source files
  root: '',

  // Base public path - important for WordPress
  base: process.env.NODE_ENV === 'development'
    ? '/'
    : '/wp-content/themes/summit-law-theme/dist/',

  build: {
    // Output directory for production builds
    outDir: 'dist',
    emptyOutDir: true,

    // Generate manifest.json for WordPress to map files
    manifest: true,

    rollupOptions: {
      // Entry points - what Vite should bundle
      input: {
        main: path.resolve(__dirname, 'src/main.js'),
      },

      output: {
        // Output file naming pattern
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name].js',
        assetFileNames: (assetInfo) => {
          // CSS files go to css/ directory
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          // Fonts go to fonts/ directory
          if (/\.(woff|woff2|eot|ttf|otf)$/i.test(assetInfo.name)) {
            return 'fonts/[name][extname]';
          }
          // Images go to img/ directory
          if (/\.(png|jpe?g|svg|gif|webp)$/i.test(assetInfo.name)) {
            return 'img/[name][extname]';
          }
          // Everything else goes to assets/
          return 'assets/[name][extname]';
        }
      }
    },

    // Minification (using esbuild - faster and built-in)
    minify: 'esbuild',

    // Source maps for debugging
    sourcemap: process.env.NODE_ENV === 'development'
  },

  server: {
    // Development server configuration
    host: '0.0.0.0', // Accept connections from Docker containers
    cors: true,
    strictPort: false,
    port: 5173,
    https: false,

    // Important: Open browser to your Local WP site
    open: false,

    hmr: {
      host: 'localhost',
      protocol: 'ws'
    }
  },

  // Resolve aliases for cleaner imports
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@css': path.resolve(__dirname, './src/css'),
      '@js': path.resolve(__dirname, './src/js'),
      '@blocks': path.resolve(__dirname, './blocks')
    }
  }
});
