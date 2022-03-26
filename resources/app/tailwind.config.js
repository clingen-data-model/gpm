module.exports = {
    purge: [
        './src/**/*.{vue,js}',
        './assets/*'
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {
            fontSize: {
                'sm': '.825rem',
                'md-lg': '1.01rem'
            },
            width: {
                '36': '9rem',
            },
            maxWidth: {
                '28': '7rem',
                '32': '8rem',
                '36': '9rem',
                '40': '10rem',
                '48': '12rem',
                '56': '14rem',
                '60': '15rem',
                '64': '16rem',
                '68': '17rem',
                '72': '18rem',
                '76': '19rem',
                '80': '20rem',
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
        screens: {
            'sm': '640px',
            'md': '768px',
            'lg': '1024px',
            'xl': '1280px',
            '2xl': '1536px',
            'print': {'raw': 'print'}
        },
    },
    variants: {
        extend: {
            borderStyle: ['active', 'hover', 'first', 'last'],
            borderRadius: ['first', 'last'],
            padding: ['active'],
            backgroundColor: ['even', 'odd', 'active'],
            opacity: ['disabled'],
            cursor: ['disabled'],
            gradientColorStops: ['active'],
        },
    },
    plugins: [],
}