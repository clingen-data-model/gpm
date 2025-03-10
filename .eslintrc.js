module.exports = {
    root: true,
    env: {
        node: true,
        es2022: true,
    },
    'extends': [
        'plugin:vue/vue3-essential',
        'eslint:recommended'
    ],
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
        'vue/no-setup-props-destructure': 'warn'
    },
    overrides: [
        {
            files: [
                '**/__tests__/*.{j,t}s?(x)',
                '**/tests/unit/**/*.spec.{j,t}s?(x)'
            ],
            env: {
                mocha: true
            }
        },
        {
            files: [
                '**/__tests__/*.{j,t}s?(x)',
                '**/tests/unit/**/*.spec.{j,t}s?(x)'
            ],
            env: {
                mocha: true
            }
        }
    ]
}
