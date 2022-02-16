let outputDir = '../../public';
let indexPath = '../resources/views/app.blade.php';

const isDockerBuild = () => process.env.BUILD_ENV === 'docker';
const isProduction = () => process.env.NODE_ENV === 'production';

if (isDockerBuild() && isProduction()) {
    outputDir = './dist';
    indexPath = './index.html';
}

module.exports = {
    devServer: {
        proxy: 'http://localhost:8080'
    },
    outputDir: outputDir,
    indexPath: indexPath,
    chainWebpack: config => {
        config
        .plugin('html')
        .tap(args => {
          args[0].title = 'ClinGen GPM'
          return args
        })
      }
}
