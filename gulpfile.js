'use strict';

const gulp = require('gulp');
const prefixer = require('gulp-autoprefixer');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const cssmin = require('gulp-clean-css');
const browserSync = require('browser-sync').create();
const rename = require('gulp-rename');
const plumber = require('gulp-plumber');
const notify = require('gulp-notify');
const stylelint = require('gulp-stylelint');
const imagemin = require('gulp-imagemin');
const svgSprite = require('gulp-svg-sprites');


// const supportFunctions = {
//     toObject(paths) {
//         const result = {};
//
//         paths.forEach(function (path) {
//             result[path.split('/').slice(-1)[0].split('.')[0]] = path;
//         });
//
//         return result;
//     },
//     removeDeletedFile(filepath) {
//         const filePathFromSrc = nodepath.relative(nodepath.resolve('src'), filepath);
//         // Concatenating the 'build' absolute path used by gulp.dest in the scripts task
//         const destFilePath = nodepath.resolve('dist', filePathFromSrc);
//         del.sync(destFilePath);
//     }
// };

// gulp.task('set-dev-node-env', function (done) {
//   process.env.NODE_ENV = config.env = 'development';
//   done();
// });
//
// gulp.task('set-prod-node-env', function (done) {
//   process.env.NODE_ENV = config.env = 'production';
//   done();
// });

const path = {
    lint: {
        style: ['web/sass/**/*.scss']
    },
    build: {
        style: 'web/css/admin/',
        svgSprite: {
            folder: 'app/Resources/views/svg-sprites/',
            file: '_svg-sprite.html'
        }
    },
    src: {
        style: 'web/sass/*.scss',
        svgSprite: 'web/img/svg-sprite/*.svg'
    },
    watch: {
        style: 'web/sass/**/*.scss',
    },
    // clean: ['dist/**', '!dist/']
};



// const config = {
//   server: {
//     baseDir: './dist'
//   },
//   host: 'localhost',
//   port: 3000
// };

gulp.task('css:lint', function (done) {
    gulp.src(path.lint.style, {since: gulp.lastRun('css:lint')})
        .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
        .pipe(stylelint({
            failAfterError: true,
            syntax: 'scss',
            reporters: [
                {formatter: 'string', console: true}
            ]
        }));
    done();
});


gulp.task('css.uncritical:build', function () {
    return gulp.src(path.src.style)
        .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(prefixer({
            browsers: ['last 2 versions'],
            cascade: false
        }))
        .pipe(cssmin())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(path.build.style))
        .pipe(browserSync.reload({
            stream: true
        }))

});

gulp.task('svg-build', function () {
  return gulp.src(path.src.svgSprite)
      // .pipe(plumber({errorHandler: notify.onError('Error: <%= error.message %>')}))
      // .pipe(imagemin([
      //   imagemin.svgo({
      //     plugins: [
      //       {removeViewBox: false},
      //       {removeTitle: false},
      //       {cleanupIDs: true}
      //     ]
      //   })
      // ]))
      .pipe(svgSprite({
        mode: 'symbols',
        preview: false,
        svg: {
          symbols: path.build.svgSprite.file
        },
        transformData(data) {
          data.svg.map((item) => {
            // change id attribute
            item.data = item.data.replace(/id="([^"]+)"/gm, `id="${item.name}-$1"`);

            // change id in fill attribute
            item.data = item.data.replace(/fill="url\(#([^"]+)\)"/gm, `fill="url(#${item.name}-$1)"`);

            // change id in mask attribute
            item.data = item.data.replace(/mask="url\(#([^"]+)\)"/gm, `mask="url(#${item.name}-$1)"`);

            // change id in filter attribute
            item.data = item.data.replace(/filter="url\(#([^"]+)\)"/gm, `filter="url(#${item.name}-$1)"`);

            // replace double id for the symbol tag
            item.data = item.data.replace(`id="${item.name}-${item.name}"`, `id="${item.name}-$1"`);
            return item;
          });
          return data; // modify the data and return it
        }
      }))
      .pipe(gulp.dest(path.build.svgSprite.folder))
      // .pipe(reload({
      //   stream: true
      // }));
});

gulp.task('browserSync', function() {
    browserSync.init({
        proxy: {
            target: "en.ereferer.lc",
            ws: true
        }
    });
    gulp.watch(path.watch.style, gulp.series('css.uncritical:build'));

    browserSync.watch('app/Resources/views/**/*.twig').on('change', browserSync.reload);
});

gulp.task('watch', gulp.parallel('browserSync'), function () {
    gulp.watch(path.watch.style, gulp.series('css.uncritical:build'));

});

gulp.task('default', gulp.series('watch', gulp.parallel('browserSync')));

