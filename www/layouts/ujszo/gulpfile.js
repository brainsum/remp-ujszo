'use strict';

/**
 * Import required node modules
 */
const autoprefixer        = require('autoprefixer');
const browserSync         = require('browser-sync').create();
const Fiber               = require('fibers');
const gulp                = require('gulp');
const npmDist             = require('gulp-npm-dist');
const postcss             = require('gulp-postcss');
const sass                = require('gulp-sass');
const sourcemaps          = require('gulp-sourcemaps');
const splitMediaQueries   = require('gulp-split-media-queries');
const stylelint           = require('gulp-stylelint');
const customProperties    = require('postcss-custom-properties');

/**
 * Sass settings
 *
 * Set Sass compiler. There are two options:
 * - require('sass') for Dart Sass
 * - require('node-sass') for Node Sass (LibSass)
 */
sass.compiler             = require('sass');

/**
 * Gulp config
 */
const config = {
  paths: {
    styles: {
      src: './sass/**/*.scss',
      dest: './css/',
    }
  },
  // Desktop/tablet media queries in a separated CSS file.
  cssSplitting: {
    // If you change this, change it in libraries.yaml too!
    breakpoint: 48, // 768px and above (from your mqs, here we use in ems)
  },
  browserSync: {
    proxy: 'projectname.test',
    autoOpen: false,
    browsers: [
      'Google Chrome',
    ],
  },
};
// pages: require('./critical.json') // define here page types

/**
 * Copy:dependencies Task
 *
 * from npm_modules to ./public/vendors/
 * @see https://github.com/dshemendiuk/gulp-npm-dist
 * @return {object} Copied distributed version of vendor assets.
 */
function copyVendorTask() {
  return gulp
    .src(npmDist(), { base: './node_modules' })
    .pipe(gulp.dest('./vendors/'));
}

/**
 * SASS:Development Task
 *
 * Sass task for development with live injecting into all browsers
 * @return {object} Autoprefixed CSS files with expanded style and sourcemaps.
 */
function sassDevTask(done) {
  return gulp
    .src(config.paths.styles.src)
    .pipe(sourcemaps.init({ largeFile: true }))
    .pipe(sass({
      fiber: Fiber,
      outputStyle: 'expanded',
      precision: 10
    }))
    .on('error', sass.logError)
    .pipe(postcss([
      customProperties(),
      autoprefixer()
    ]))
    .pipe(sourcemaps.write({ includeContent: false }))
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(config.paths.styles.dest))
    .pipe(browserSync.stream());
}

/**
 * SASS:Production Task
 *
 * Sass task for production with linting, to be stored in Git (run before
 * commit)
 * @return {object} Autoprefixed, minified, ordered and linted* CSS files without
 * sourcemaps.
 */
function sassProdTask(done) {
  gulp
    .src(config.paths.styles.src)
    .pipe(stylelint({
      fix: true,
      reporters: [
        {
          formatter: 'verbose',
          console: true,
        },
      ],
    }))
    .pipe(sass({
      fiber: Fiber,
      precision: 10
    }))
    .on('error', sass.logError)
    .pipe(postcss([
      customProperties(),
      autoprefixer()
    ]))
    .pipe(splitMediaQueries({
      breakpoint: config.cssSplitting.breakpoint,
    }))
    .pipe(gulp.dest(config.paths.styles.dest));
  done();
}

/**
 * SASS:Linting Task
 *
 * @return {object} Linted version of SASS (auto fixable) and warnings printed to
 * console.
 */
function sassLintTask(done) {
  gulp
    .src(config.paths.sass)
    .pipe(stylelint({
      fix: true,
      reporters: [
        {
          formatter: 'verbose',
          console: true,
        },
      ],
    }));
  done();
}

/**
 * BrowserSync Task
 *
 * Watching Sass and JavaScript source files for changes.
 * @prop {string} proxy Change it for your local setup.
 * @param {function} done Changed event.
 */
function watchTask(done) {
  gulp.watch(config.paths.styles.src, sassDevTask);
  done();
}

// Define complex tasks
const compileTask = gulp.parallel(sassDevTask);
const compileProdTask = gulp.parallel(sassProdTask);

// export tasks
exports.default = gulp.series(copyVendorTask, compileTask, watchTask);
exports.prod = gulp.series(copyVendorTask, compileProdTask);
exports.lint = gulp.parallel(sassLintTask);
exports.vendors = copyVendorTask;
exports.sassDev = sassDevTask;
exports.sassProd = sassProdTask;
exports.sassLint = sassLintTask;
exports.watch = watchTask;
