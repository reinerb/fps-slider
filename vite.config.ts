import { defineConfig } from 'vite';

/**
 * Builds the front-end bundle for the [fps-slider] shortcode.
 *
 * Output is a single self-executing script plus one stylesheet with stable
 * filenames, so the PHP side can enqueue them directly (cache-bust with
 * filemtime) without reading a manifest:
 *
 *   dist/fps-slider.js
 *   dist/fps-slider.css
 */
export default defineConfig(({ mode }) => ({
  // Emit relative URLs so any assets referenced from CSS resolve
  // against the plugin's dist/ folder, wherever WordPress serves it from.
  base: './',

  // No static public/ folder for this plugin.
  publicDir: false,

  build: {
    outDir: 'dist',
    emptyOutDir: true,
    target: 'es2020',
    sourcemap: mode !== 'production',
    // Keep fonts/images as real files rather than inlining into the CSS.
    assetsInlineLimit: 0,
    // Emit CSS as its own file (for wp_enqueue_style) instead of injecting
    // it from the IIFE bundle at runtime.
    cssCodeSplit: false,
    rolldownOptions: {
      input: {
        'fps-slider': 'assets/main.ts',
      },
      output: {
        // Classic script: works with a plain wp_enqueue_script(), no
        // type="module" needed, and keeps everything out of the global scope.
        format: 'iife',
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (asset) =>
          asset.names.some((name) => name.endsWith('.css'))
            ? 'fps-slider.css'
            : 'assets/[name][extname]',
      },
    },
  },
}));
