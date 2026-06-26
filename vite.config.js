import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/app.js", // your normal livewire/ui stuff
                "resources/js/pos-app.js", // POS entry
            ],
            refresh: true,
        }),
        vue(),
    ],
});
