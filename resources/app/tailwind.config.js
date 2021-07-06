module.exports = {
    purge: [
        './src/**/*.vue'
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {
            maxWidth: {
                '28': '7rem',
                '32': '8rem',
                '40': '10rem',
                '48': '12rem',
                '56': '14rem',
                '60': '16rem',
                '64': '20rem',
            },
            minHeight: {
                '0': '0',
                '1/4': '25%',
                '1/2': '50%',
                '2/3': '66%',
                '3/4': '75%',
                'full': '100%'
            },
            minWidth: {
                '4': '1rem',
                '8': '2rem',
                '16': '4rem',
                '24': '6rem',
                '28': '7rem',
                '32': '8rem',
                '40': '10rem',
                '48': '12rem',
            },
        },
        container: {
            padding: {
                DEFAULT: '1rem',
                sm: '1rem',
                md: '2rem',
                lg: '3rem'
            }
        },
    },
    variants: {
        extend: {
            borderStyle: ['active', 'hover', 'first', 'last'],
            padding: ['active'],
            backgroundColor: ['even', 'odd', 'active'],
            opacity: ['disabled'],
            cursor: ['disabled'],
            gradientColorStops: ['active'],

        },
    },
    plugins: [],
}