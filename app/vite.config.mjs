import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import symfonyPlugin from "vite-plugin-symfony";
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
import { viteStaticCopy } from 'vite-plugin-static-copy'
import { dirname, resolve } from "node:path";
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url))

export default defineConfig({
    plugins: [
        vue(),
        VueI18nPlugin({
        }),
        symfonyPlugin(),
        viteStaticCopy({
            targets: [
                // {
                //     src: resolve(__dirname, 'node_modules/ugent-huisstijl-bootstrap5/dist/fonts/*'),
                //     dest: 'fonts'
                // },
                {
                    src: resolve(__dirname, 'node_modules/ugent-huisstijl-bootstrap5/dist/images/*'),
                    dest: 'images'
                },
                // {
                //     src: resolve(__dirname, 'node_modules/\@fortawesome/fontawesome-free/webfonts/*'),
                //     dest: 'webfonts'
                // },
            ]
        })
    ],

    build: {
        manifest: 'manifest.json',
        outDir: "public/build",
        rollupOptions: {
            input: {
                'main': "./assets/js/apps/main.js",
                'text-search': "./assets/js/apps/text-search.js",
                'text-view': "./assets/js/apps/text-view.js",
            },
        }
    },
    server: {
        // Respond to all network requests
        host: true,
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173'
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'assets/js'),
            '@assets': resolve(__dirname, 'assets'),
            'vue': 'vue/dist/vue.esm-bundler',
            // required for ugent huisstijl
            '~bootstrap': resolve(__dirname, 'node_modules/bootstrap'),
            '~bootstrap-icons': resolve(__dirname, 'node_modules/bootstrap-icons'),
            '~@fortawesome': resolve(__dirname, 'node_modules/@fortawesome'),
            '~@eonasdan': resolve(__dirname, 'node_modules/@eonasdan'),
        },
        extensions: ['.js', '.ts', '.tsx', '.jsx', '.vue'],
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                // hide sass @import deprecations
                silenceDeprecations: ['import']
            }
        }
    },
    test: {
        globals: true,
        environment: 'jsdom',
    }
});
