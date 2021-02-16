module.exports = {
    purge: [
        './src/**/*.vue'
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {},
        container: {
            padding: '3rem'
        },
        minHeight: {
            '0': '0',
            '1/4': '25%',
            '1/2': '50%',
            '2/3': '66%',
            '3/4': '75%',
            'full': '100%'
        }
    },
    variants: {
        extend: {
            backgroundColor: ['even', 'odd', 'active'],
            opacity: ['disabled'],
            cursor: ['disabled'],
            gradientColorStops: ['active']
        },
    },
    plugins: [],
}