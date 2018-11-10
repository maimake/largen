const mix = require('laravel-mix');
const ini = require('ini');
const fs = require('fs');

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
