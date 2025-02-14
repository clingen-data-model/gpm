import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'

const routes = () => import('resources/js/router/index.js')

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                }
            }
        }),
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'],
            refresh: true,
        }),
    ],
    test: {
        globals: true,
        environment: "jsdom",
    },
    resolve: {
        alias: {
            '~': path.resolve(__dirname, 'node_modules'),
            '@': path.resolve(__dirname, 'resources/js'),
            // '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
        // FIXME: should remove this when have added .vue extension to all component references
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue'],
    }
})
