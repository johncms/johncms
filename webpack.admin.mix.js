let mix = require('laravel-mix');
let WebpackRTLPlugin = require('webpack-rtl-plugin');
mix.disableNotifications();
mix.js('themes/admin/src/js/app.js', 'themes/admin/assets/js')
        .sass('themes/admin/src/scss/app.scss', 'themes/admin/assets/css')
        .webpackConfig({
            plugins: [
                new WebpackRTLPlugin()
            ]
        })
        .options({
            processCssUrls: false
        });

mix.sourceMaps(true, 'source-map');
mix.extract();
