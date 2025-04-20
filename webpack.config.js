const Encore = require('@symfony/webpack-encore');
const path = require('path');
const dotenv = require('dotenv');
const fs = require('fs');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('audience_index', './assets/js/audience/index.js')
    .addEntry('affaire_note_audience', './assets/js/affaire/note_audience.js')
    .addEntry('affaire_index', './assets/js/affaire/index.js')
    .addEntry('affaire_decision', './assets/js/affaire/decision.js')
    .addEntry('affaire_renvoi', './assets/js/affaire/renvoi.js')
    .addEntry('affaire_representant_legal', './assets/js/affaire/representant_legal.js')
    .addEntry('keep_alive_selenium', './assets/js/keep_alive_selenium.js')
    .addEntry('login', './assets/security/login.js')
    .addEntry('import_calendar', './assets/js/import/calendar.js')
    .addEntry('import_audience', './assets/js/import/audience.js')
    .addEntry('import_iteratif_index', './assets/js/import-iteratif/index.js')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    .enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()
    .copyFiles([
        { from: './assets/images', to: 'images/[path][name].[ext]' },
        { from: './assets/ckeditor/plugins', to: 'ckeditor/plugins/[path][name].[ext]' },
        { from: './node_modules/remixicon/fonts', to: 'remixicon/fonts/[path][name].[ext]' },
        { from: './node_modules/ckeditor4/', to: 'ckeditor/[path][name].[ext]', pattern: /\.(js|css)$/, includeSubdirectories: false},
        { from: './node_modules/ckeditor4/adapters', to: 'ckeditor/adapters/[path][name].[ext]'},
        { from: './node_modules/ckeditor4/lang', to: 'ckeditor/lang/[path][name].[ext]'},
        { from: './node_modules/ckeditor4/plugins', to: 'ckeditor/plugins/[path][name].[ext]'},
        { from: './node_modules/ckeditor4/skins', to: 'ckeditor/skins/[path][name].[ext]'},
        { from: './node_modules/ckeditor4/vendor', to: 'ckeditor/vendor/[path][name].[ext]'},
        { from: './assets/js/serviceWorker', to: 'serviceWorker/[path][name].[ext]'},
        { from: './node_modules/dexie/dist', to: 'dexie/[path][name].[ext]', pattern: /dexie\.js.*$/, includeSubdirectories: false},
    ])
;

const config = Encore.getWebpackConfig();
module.exports = config;
module.exports.externals = [{ 'bazinga-translator': 'Translator' }];
