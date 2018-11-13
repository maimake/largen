const mix = require('laravel-mix');
const ini = require('ini');
const fs = require('fs');
const _ = require('lodash');

exports.resolvePath = function (dir) {
    return path.join(__dirname, dir);
};


/*
 |--------------------------------------------------------------------------
 | Config webpack
 |--------------------------------------------------------------------------
 |
 */

Mix.listen('configReady', webpackConfig => {
    // console.log(webpackConfig);
    // console.log(webpackConfig['plugins'][6]);
    if (mix.config.hmr)
    {
        console.log(`webpack-dev-server URL: htp://${webpackConfig.devServer.host}:${webpackConfig.devServer.port}/webpack-dev-server`);
    }
});


let defaultAlias = {
    resolve: {
        extensions: ['.scss', '.json'],
        alias: {
            '@': exports.resolvePath('resources'),
        }
    },
};

let webpackConfig = {
};

mix.webpackConfig(_.merge(defaultAlias, webpackConfig));

if (mix.config.hmr)
{
    mix.options({
        hmrOptions: {
            port: process.env.DEV_PORT,
        }
    })

} else {
    // version by query string
    mix.version();
}


/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css');



/*
 |--------------------------------------------------------------------------
 | Browser sync
 |--------------------------------------------------------------------------
 |
 */


if (process.env.APP_ENV === 'local') {
    let config = ini.parse(fs.readFileSync('./.env', 'utf-8'));
    mix.browserSync({
        proxy: config.APP_URL,
        browser: "google chrome",
        files: [
            'app/**/*.php',
            'resources/views/**/*.php',
            'public/js/**/*.js',
            'public/css/**/*.css',
            'public/static/**/*.js',
            'public/static/**/*.css',
            'public/apps/**/*.js',
            'public/apps/**/*.css',
        ],
    });
}

mix.disableNotifications();


