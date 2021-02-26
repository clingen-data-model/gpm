module.exports = {
    devServer: {
        port: 8080,
        proxy: 'http://localhost:8080'
    },

    outputDir: '../../public',
    publicPath: '/',

    indexPath: process.env.NODE_ENV === 'production'
        ? '../resources/views/app.blade.php'
        : 'index.html',
}