import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';
import { defineConfig } from 'eslint/config';

export default defineConfig(
    { ignores: ['node_modules/**', 'vendor/**', 'public/build/**'] },
    js.configs.recommended,
    tseslint.configs.recommended,
    vue.configs['flat/recommended'],
    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            // 未定義参照は vue-tsc が検出するため、DOM 型を誤検出する no-undef は無効化する。
            'no-undef': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/html-indent': ['error', 4],
        },
    },
);
