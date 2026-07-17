let mix = require('laravel-mix');
let WebpackRTLPlugin = require('webpack-rtl-plugin');
mix.disableNotifications();
// The document root. Asset URLs in mix-manifest.json are relative to it.
mix.setPublicPath('public');
mix.js('themes/default/src/js/app.js', 'public/themes/default/assets/js').vue()
        .sass('themes/default/src/scss/app.scss', 'public/themes/default/assets/css')
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
