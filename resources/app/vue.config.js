module.exports = {
    devServer: {
        proxy: 'http://localhost:8080'
    },

    outputDir: process.env.NODE_ENV === 'production' && process.env.BUILD_ENV === 'docker'
        ? './dist'
        : '../../public',

    indexPath: process.env.NODE_ENV === 'production' && !process.env.BUILD_ENV === 'docker'
        ? '../resources/views/app.blade.php'
        : 'index.html',
}
