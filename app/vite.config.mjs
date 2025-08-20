import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import symfonyPlugin from "vite-plugin-symfony";
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'
import { viteStaticCopy } from 'vite-plugin-static-copy'
import path from "node:path";
import { resolve } from 'path'

export default defineConfig({
    plugins: [
        vue(),
        VueI18nPlugin({
        }),
        symfonyPlugin(),
        viteStaticCopy({
            targets: [
                {
                    src: './assets/images',
                    dest: 'images'
                }
            ]
        })
    ],
    build: {
        manifest: true,
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
            '@': path.resolve(__dirname, './assets/js'),
            '@assets': path.resolve(__dirname, './assets'),
            'vue': 'vue/dist/vue.esm-bundler',
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
