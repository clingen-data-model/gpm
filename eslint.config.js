import antfu from '@antfu/eslint-config'

export default antfu(
    {
        typescript: true,
        vue: true,
        stylistic: false,
    },
    {
        ignores: ['resources/js/tests/**', '**/specs/*', '**/*.spec.js', '**/*.spec.ts'],
    },
    {
        files: ['resources/js/**/*.{js,ts,vue,json}'],
        rules: {
            'vue/eqeqeq': 'off',
            'vue/order-in-components': 'off',
            'jsonc/no-useless-escape': 'off',
            'perfectionist/sort-imports': 'off',
            'perfectionist/sort-named-imports': 'off',
            'perfectionist/sort-named-exports': 'off',
        }
    },
)
