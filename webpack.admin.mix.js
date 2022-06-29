let mix = require('laravel-mix');
require('laravel-mix-eslint');
let WebpackRTLPlugin = require('webpack-rtl-plugin');

mix.webpackConfig({
  resolve: {
    extensions: ['.js', '.vue', '.json', '.ts'],
    alias: {
      '@': __dirname + '/themes/admin/src/js',
    },
  },
  plugins: [
    mix.inProduction() ? new WebpackRTLPlugin() : () => {
    }
  ]
});

mix.ts('themes/admin/src/js/app.ts', 'themes/admin/assets/js')
  .disableSuccessNotifications()
  .vue({version: 3})
  .eslint()
  .sass('themes/admin/src/scss/app.scss', 'themes/admin/assets/css')
  .options({
    processCssUrls: false,
    manifest: false
  });

//mix.sourceMaps(false, 'source-map');
mix.extract();

/*if (!mix.inProduction()) {
  mix.webpackConfig({
    devtool: 'inline-source-map'
  })
}*/
