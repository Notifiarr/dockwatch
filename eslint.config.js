import js from '@eslint/js';

export default [
    js.configs.recommended,
    {
        files: ['root/app/www/public/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 12,
            sourceType: 'script',
            globals: {
                browser: true,
                jquery: true,
                $: true,
                jQuery: true
            }
        },
        rules: {
            indent: ['error', 4, { SwitchCase: 1 }],
            'linebreak-style': ['warn', 'unix'],
            quotes: ['error', 'single'],
            semi: ['error', 'always'],
            'no-unused-vars': 'off',
            'no-undef': 'off'
        }
    }
];
