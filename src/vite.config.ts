import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = { ...loadEnv(mode, process.cwd(), ''), ...process.env };
    const port = Number(env.VITE_PORT || 5173);

    if (!Number.isInteger(port) || port < 1 || port > 65535) {
        throw new Error('VITE_PORT must be an integer between 1 and 65535.');
    }

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.ts'],
                refresh: true,
            }),
            vue(),
        ],
        css: {
            preprocessorOptions: {
                scss: {
                    quietDeps: true,
                },
            },
        },
        server: {
            host: '0.0.0.0',
            port,
            strictPort: true,
            cors: {
                origin: env.APP_URL || 'http://localhost:8000',
            },
            hmr: {
                host: 'localhost',
                clientPort: port,
            },
        },
    };
});
