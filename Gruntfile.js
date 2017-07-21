var bowerPath       = 'bower_components/',
    composerPath    = 'vendor/',
    sassPath        = 'assets/src/sass/',
    jsPath          = 'assets/src/js/',
    cssBuildPath    = 'assets/dist/css/',
    jsBuildPath     = 'assets/dist/js/',
    cssBuildPathDev = 'assets/dist/css/',
    jsBuildPathDev  = 'assets/dist/js/'
    loadPaths       = [
        bowerPath,
        composerPath
    ];

var script_js = [
    bowerPath + "/jquery/dist/jquery.js",

    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/affix.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/alert.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/button.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/carousel.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/collapse.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/modal.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/popover.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/scrollspy.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/tab.js",
    bowerPath + "/bootstrap-sass/assets/javascripts/bootstrap/transition.js",

    composerPath + "bower-asset/bootstrap-datepicker/dist/js/bootstrap-datepicker.js",
];

var javaScriptDev = {};
var javaScriptProd = {};

javaScriptDev[ jsBuildPath + 'log-viewer.js' ] = script_js
javaScriptDev[ jsBuildPath + 'log-viewer.js' ].push( bowerPath + "/vue/dist/vue.js" );
javaScriptDev[ jsBuildPath + 'log-viewer.js' ].push( jsPath + "/**/*.js" );
javaScriptDev[ jsBuildPath  + 'log-viewer.js' ].push( '!' + jsPath + "/vue-prod.js" );

javaScriptProd[ jsBuildPath  + 'log-viewer.js' ] = script_js
javaScriptProd[ jsBuildPath  + 'log-viewer.js' ].push( bowerPath + "/vue/dist/vue.min.js" );
javaScriptProd[ jsBuildPath  + 'log-viewer.js' ].push( jsPath + "/**/*.js" );

module.exports = function(grunt) {
    grunt.initConfig({
        vars:{
            changedFile : ""
        },
        exec: {
            staticsite: {
                cmd: function (filename) {
                    return 'php vendor/bin/staticsite';
                },
                stdout: true
            }
        },
        sass: {
            dev: {
                update:true,
                options: {
                    loadPath:loadPaths
                },
                files: [{
                    expand: true,
                    cwd: sassPath,
                    src: ['*.scss', '*.sass'],
                    dest: cssBuildPathDev,
                    ext: '.css'
                }]
            },
            prod: {
                options: {
                    sourcemap:false,
                    precision: 2,
                    loadPath: loadPaths
                },
                files: [ {
                    expand: true,
                    cwd: sassPath,
                    src: ['*.scss', '*.sass'],
                    dest: cssBuildPath,
                    ext: '.css'
                } ]
            }
        },
        uglify: {
            dev: {
                options: {
                    mangle:false,
                    sourceMap: false,
                    beautify: true
                },
                files: javaScriptDev
            },
            prod: {
                options: {
                    sourceMap: false,
                    mangle:true,
                    compress:true
                },
                files: javaScriptProd
            }
        },
        postcss: {
            prod: {
                options: {
                    processors: [
                        require('postcss-import')({path:loadPaths}),
                        // require('postcss-uncss')( { html: [ 'build/**/*.html' ] } ),
                        require('autoprefixer')( { browsers: 'last 2 versions' } ),
                        require("css-mqpacker")(),
                        require('cssnano')( { discardComments:{ removeAll: true } } )
                    ]
                },
                src: cssBuildPath + '/**/*.css'
            },
            dev: {
                options: {
                    map: false,
                    processors: [
                        require('postcss-import')({path:loadPaths}),
                        // require('postcss-uncss')( { html: [ 'public/**/*.html' ] } ),
                        require('autoprefixer')({browsers: 'last 2 versions'})
                    ]
                },
                src: cssBuildPathDev + '/**/*.css'
            }
        },
        clean: {
            public: [ 'public' ],
            build: [ 'build'  ],
            dep: [ '.sass-cache', 'bower_components', 'vendor', 'node_modules', 'composer.lock'  ]
        },
        watch : {
            js : {
                files : 'assets/src/js/**/*',
                tasks : [ 'uglify:dev' ],
            },
            sass : {
                files :[ 'assets/src/sass/**/*.sass', 'assets/src/sass/**/*.scss'],
                tasks : ['sass:dev', 'postcss:dev'],
            },
        },
    });

    /*
     * Load npm grunt tasks
     */
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-postcss');
    grunt.loadNpmTasks('grunt-contrib-watch');

    // change config var to changed file when a file is changed
    grunt.event.on('watch', function(action, filepath) {
        grunt.config('vars.changedFile', filepath);
    });

    grunt.registerTask(
        'buildProd',
        [
        'sass:prod',
        'uglify:prod',
        'postcss:prod',
        ]
    );
    grunt.registerTask(
        'buildDev', 
        [ 
            'sass:dev',
            'postcss:dev',
            'uglify:dev',
        ]
    );
    grunt.registerTask( 'build', [ 'buildProd' ] );
    grunt.registerTask( 'default', [ 'buildDev', 'watch' ] );
};