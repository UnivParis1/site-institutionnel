const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));

var autoprefixer = require('gulp-autoprefixer'),
    csso = require("gulp-csso"),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    gzip = require('gulp-gzip'),
    sourcemaps = require('gulp-sourcemaps'),
    del = require('del');
var $    = require('gulp-load-plugins')();

var sassPaths = [
  'node_modules/foundation-sites/scss',
  'node_modules/motion-ui/src'
];

/* Traitement des CSS */
gulp.task('styles', function() {
  return gulp.src('scss/*.scss')

    .pipe(sourcemaps.init()) // added by Tuesday

    .pipe($.sass({
      includePaths: sassPaths
    })
    .on('error', $.sass.logError))
    .pipe(autoprefixer({ browsers: ['last 2 versions', 'ie >= 9'] }))
	  .pipe(gulp.dest('css'))
    .pipe(csso())

    //.pipe(rename({suffix: '.min'})) // commented by Tuesday
    //.pipe(gulp.dest('css')) // commented by Tuesday

    //.pipe(gzip()) // commented by Tuesday
    //.pipe(gulp.dest('css')) // commented by Tuesday

    .pipe(sourcemaps.write('.')) // added by Tuesday
    .pipe(gulp.dest("css")) // added by Tuesday
});

/* Traitement des JS */
gulp.task('scripts', function() {
    return gulp.src('js/src/*.js')
    .pipe(concat('script.js'))
    .pipe(gulp.dest('js'))
    .pipe(uglify())
    .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest('js'))
    .pipe(gzip())
    .pipe(gulp.dest('js'))
});


gulp.task('default', gulp.series('styles', 'scripts'), function(done) {

});
