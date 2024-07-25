const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');

// Chemins des fichiers SASS et CSS
const paths = {
  sass: {
    src: 'sass/**/*.scss',
    dest: 'css'
  }
};

// Tâche pour compiler SASS en CSS
function compileSass() {
  return gulp.src(paths.sass.src)
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(sourcemaps.write('./maps'))
    .pipe(gulp.dest(paths.sass.dest));
}

// Tâche de surveillance
function watchFiles() {
  gulp.watch(paths.sass.src, compileSass);
}

const build = gulp.series(compileSass, watchFiles);

exports.default = build;
