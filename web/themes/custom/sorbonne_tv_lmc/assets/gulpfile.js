const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCss = require('gulp-clean-css');
const rename = require('gulp-rename');
const concat = require('gulp-concat');
const minify = require('gulp-minify');

function compileSass() {
    return gulp.src('./src/scss/*.scss')
        .pipe(sass.sync({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(cleanCss())
        .pipe(rename({ suffix: '.min' }))
        .pipe(gulp.dest('./dist/css'));
}
function compileScripts() {
    return gulp.src('./src/js/*.js')
        .pipe(concat('custom.js'))
        .pipe(minify())
        .pipe(gulp.dest('./dist/js'));
}


gulp.task('sass', compileSass);
gulp.task('js', compileScripts);