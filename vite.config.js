import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import rtlcss from 'rtlcss';

/**
 * Collects the build entry points of every installed theme.
 *
 * A theme declares them in its manifest (themes/<name>/theme.php, the "entries" key), which is
 * also what the application reads to decide which bundle a page loads. Listing them here as well
 * would mean two lists to keep in sync, so this one is derived from the manifests: a new theme
 * is built as soon as it is added, without touching the build configuration.
 */
function themeEntries() {
    const themesDir = 'themes';
    const entries = [];

    if (! existsSync(themesDir)) {
        return entries;
    }

    for (const theme of readdirSync(themesDir, { withFileTypes: true })) {
        if (! theme.isDirectory()) {
            continue;
        }

        const manifest = `${themesDir}/${theme.name}/theme.php`;

        if (! existsSync(manifest)) {
            continue;
        }

        // The manifest is PHP, so the paths are read from its "entries" block rather than
        // evaluated. A theme that builds its entries some other way declares nothing here and
        // is expected to be added to this config by hand.
        const block = readFileSync(manifest, 'utf8').match(/'entries'\s*=>\s*\[([\s\S]*?)\]/);

        if (block === null) {
            continue;
        }

        for (const [, path] of block[1].matchAll(/=>\s*'([^']+)'/g)) {
            if (! existsSync(path)) {
                throw new Error(`${manifest}: the entry point "${path}" does not exist.`);
            }

            entries.push(path);
        }
    }

    return entries;
}

/**
 * Emits a right-to-left variant next to every generated stylesheet.
 *
 * The previous Webpack build produced app.rtl.css via webpack-rtl-plugin, which
 * uses rtlcss under the hood. Layouts pick the mirrored file for RTL locales,
 * so the sibling has to keep the hashed base name: app-[hash].css -> app-[hash].rtl.css
 */
function emitRtlCss() {
    return {
        name: 'johncms:emit-rtl-css',
        apply: 'build',
        generateBundle(_options, bundle) {
            for (const file of Object.values(bundle)) {
                if (file.type !== 'asset' || ! file.fileName.endsWith('.css')) {
                    continue;
                }

                this.emitFile({
                    type: 'asset',
                    fileName: file.fileName.replace(/\.css$/, '.rtl.css'),
                    source: rtlcss.process(file.source.toString()),
                });
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: themeEntries(),
            publicDirectory: 'public',
            buildDirectory: 'build',
            // Templates are rendered by PHP, so there is nothing for Vite to watch here.
            refresh: false,
        }),
        vue({
            template: {
                // Templates point at committed static files (sprites, images) that are
                // served by PHP from the public directory, they are not bundle inputs.
                transformAssetUrls: false,
            },
        }),
        emitRtlCss(),
    ],

    // The public directory is the document root, it must not be copied into itself.
    publicDir: false,

    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap still relies on APIs deprecated in Dart Sass, there is
                // nothing to fix on our side until it is updated upstream.
                quietDeps: true,
                // Bootstrap 5 is only distributed as @import-based sources, so the
                // theme entrypoints cannot move to @use before Bootstrap 6.
                silenceDeprecations: ['import'],
            },
        },
    },

    resolve: {
        alias: {
            // Vue islands are mounted on server-rendered markup, which needs the runtime compiler.
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },

    define: {
        __VUE_OPTIONS_API__: 'true',
        __VUE_PROD_DEVTOOLS__: 'false',
        __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
    },

    build: {
        rollupOptions: {
            output: {
                // The shared chunk is named after the first module that lands in it,
                // which says nothing about the libraries it actually holds.
                chunkFileNames: 'assets/vendor-[hash].js',
            },
        },
    },

    server: {
        // This address is written to the hot file and is what the browser loads the
        // modules from, so it is pinned to the IPv4 loopback instead of the default,
        // which resolves to the IPv6 one and is not reachable everywhere.
        host: '127.0.0.1',
        // The pages are served by PHP, so the modules come from another origin.
        cors: true,
    },
});
