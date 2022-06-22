let mix = require('laravel-mix');
require('laravel-mix-eslint');
let WebpackRTLPlugin = require('webpack-rtl-plugin');

mix.webpackConfig({
  resolve: {
    extensions: ['.js', '.vue', '.json', '.ts'],
    alias: {
      '@': __dirname + '/themes/default/src/js',
    },
  },
  plugins: [
    new WebpackRTLPlugin()
  ]
});

mix.ts('themes/default/src/js/app.ts', 'themes/default/assets/js')
  .disableSuccessNotifications()
  .vue({version: 3})
  .eslint()
  .sass('themes/default/src/scss/app.scss', 'themes/default/assets/css')
  .options({
    processCssUrls: false,
    manifest: false
  });

mix.sourceMaps(false, 'source-map');
mix.extract();

if (!mix.inProduction()) {
  mix.webpackConfig({
    devtool: 'inline-source-map'
  })
}
