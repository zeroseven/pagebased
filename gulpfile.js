const gulp = require('gulp');
const typescript = require('gulp-typescript');
const uglify = require('gulp-uglify');
const babel = require('gulp-babel');
const rename = require('gulp-rename');
const development = process.env.GULP_CONTEXT === 'development';

gulp.task('JavaScript', done => {
  const tsProject = typescript.createProject('tsconfig.json');
  const tsResult = gulp.src(['./Resources/Private/JavaScript/**/*.ts'])
    .pipe(tsProject())
    .on('error', () => {
    });

  tsResult.js.pipe(babel({
    presets: ['@babel/preset-env']
  }))
    .pipe(uglify(development ? {
      compress: false,
      mangle: false,
      output: {
        beautify: true
      }
    } : {}))
    .pipe(rename({suffix: '.dist.min'}))
    .pipe(gulp.dest('./Resources/Public/JavaScript'));

  done();
});

gulp.task('watchJavaScript', done => {
  gulp.watch('./Resources/Private/JavaScript/**/*.ts', gulp.series('JavaScript'));

  done();
});

gulp.task('build', gulp.parallel('JavaScript'));

gulp.task('default', gulp.series('build', gulp.parallel('watchJavaScript')));
