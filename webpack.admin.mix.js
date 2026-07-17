let mix = require('laravel-mix');
let WebpackRTLPlugin = require('webpack-rtl-plugin');
mix.disableNotifications();
// The document root. Asset URLs in mix-manifest.json are relative to it.
mix.setPublicPath('public');
mix.js('themes/admin/src/js/app.js', 'public/themes/admin/assets/js').vue()
        .sass('themes/admin/src/scss/app.scss', 'public/themes/admin/assets/css')
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
