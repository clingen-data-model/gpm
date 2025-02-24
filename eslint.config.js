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
            'vue/html-indent': 'off',
            'vue/attributes-order': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/v-on-event-hyphenation': 'off',
            'vue/first-attribute-linebreak': 'off',
            'vue/html-self-closing': 'off',
            'vue/html-quotes': 'off',
            'vue/html-closing-bracket-spacing': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/space-infix-ops': 'off',
            'vue/require-explicit-emits': 'off',
            'vue/attribute-hyphenation': 'off',
            'vue/define-macros-order': 'off',
            'vue/order-in-components': 'off',
            'jsonc/no-useless-escape': 'off',
        }
    },
)
