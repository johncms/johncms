let mix = require('laravel-mix');
let WebpackRTLPlugin = require('webpack-rtl-plugin');
mix.disableNotifications();
mix.js('themes/default/src/js/app.js', 'themes/default/assets/js').vue()
        .sass('themes/default/src/scss/app.scss', 'themes/default/assets/css')
        .webpackConfig({
            plugins: [
                new WebpackRTLPlugin()
            ]
        })
        .options({
            processCssUrls: false,
            terser: {
                parallel: true,
                extractComments: false,
                terserOptions: {
                    compress: true,
                    output: {
                        comments: false
                    }
                }
            }
        });

mix.sourceMaps(false, 'source-map');
mix.extract();

if (!mix.inProduction()) {
    mix.webpackConfig({
        devtool: 'inline-source-map'
    })
}
