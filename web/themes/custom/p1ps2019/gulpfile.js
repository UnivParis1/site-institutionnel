var gulp = require('gulp'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    csso = require("gulp-csso"),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    concat = require('gulp-concat'),
    gzip = require('gulp-gzip'),
    del = require('del');
var $    = require('gulp-load-plugins')();

var sassPaths = [
  'node_modules/foundation-sites/scss',
  'node_modules/motion-ui/src'
];

/* Traitement des CSS */
gulp.task('styles', function() {
  return gulp.src('scss/*.scss')
    .pipe($.sass({
      includePaths: sassPaths
    })
    .on('error', $.sass.logError))
    .pipe(autoprefixer({ browsers: ['last 2 versions', 'ie >= 9'] }))
	  .pipe(gulp.dest('css'))
    .pipe(csso())
    .pipe(rename({suffix: '.min'}))
    .pipe(gulp.dest('css'))
    .pipe(gzip())
    .pipe(gulp.dest('css'))
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
