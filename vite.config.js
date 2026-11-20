import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/prose.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            refresh: true,
            fonts: [
                bunny('Rubik', {
                    weights: [400, 500, 600, 700, 800],
                }),
                // Optional Inti Materi fonts (config/content.php). Self-hosted and
                // not preloaded: the browser only downloads them when material uses them.
                bunny('Plus Jakarta Sans', {
                    weights: [400, 700],
                    preload: false,
                }),
                bunny('Nunito', {
                    weights: [400, 700],
                    preload: false,
                }),
                bunny('Lora', {
                    weights: [400, 700],
                    styles: ['normal', 'italic'],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
