import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import path from 'path'

const routes = () => import('resources/js/router/index.js')

export default defineConfig(({ mode }) => ({
    define: {
        'import.meta.env.GT_FRONTEND_URL': JSON.stringify(loadEnv(mode, process.cwd(), 'GT_FRONTEND_URL').GT_FRONTEND_URL || ''),
    },
    build: {
        sourcemap: true,
    },
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
            input: ['resources/js/app.js',],
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
        },
    }
}))
