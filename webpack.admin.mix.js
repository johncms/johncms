let mix = require('laravel-mix');
mix.disableNotifications();
mix.js('themes/admin/src/js/app.js', 'themes/admin/assets/js')
        .sass('themes/admin/src/scss/app.scss', 'themes/admin/assets/css')
        .options({
            processCssUrls: false
        });

mix.sourceMaps(true, 'source-map');
mix.extract();
