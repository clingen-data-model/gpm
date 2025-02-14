import pluginVue from 'eslint-plugin-vue'
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting'

export default [
    {
        name: 'app/files-to-lint',
        files: ['**/*.js', '**/*.vue'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: ['**/node_modules/**', '**/dist/**', '**/public/**', '**/build/**', '**/vendor/**'],
    },
    ...pluginVue.configs['flat/essential'],
    skipFormatting,
    {
        rules: {
            'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'no-unused-vars': process.env.NODE_ENV === 'production' ? 'warn' : 'warn',
            'no-empty': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'no-unreachable': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'vue/no-unused-components': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'vue/no-unused-vars': process.env.NODE_ENV === 'production' ? 'warn' : 'warn',
            'vue/no-use-v-if-with-v-for': process.env.NODE_ENV === 'production' ? 'warn' : 'warn',
            'vue/multi-word-component-names': 'off',
            'vue/no-setup-props-destructure': 'warn',
        }
    },
]