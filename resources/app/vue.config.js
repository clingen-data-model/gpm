module.exports = {
    devServer: {
        port: 8080,
        proxy: 'http://localhost:8080'
    },
    chainWebpack: config => {
        config
            .plugin('html')
            .tap(args => {
                args[0].title = 'ClinGen EPAM'
                return args
            })
    },
    outputDir: '../../public',
    publicPath: '/',

    indexPath: process.env.NODE_ENV === 'production'
        ? '../resources/views/app.blade.php'
        : 'index.html',
}