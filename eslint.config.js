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
            'vue/v-on-event-hyphenation': 'off',
            'vue/require-explicit-emits': 'off',
            'vue/attribute-hyphenation': 'off',
            'jsonc/no-useless-escape': 'off',
        }
    },
)
