'use strict';
module.exports = function (grunt) {

    // Load all grunt tasks
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Clean dist folder
        clean: {
            dist: ['dist'],
            temp: ['dist/buddypress-birthdays']
        },

        // Copy files to dist folder
        copy: {
            dist: {
                files: [{
                    expand: true,
                    src: [
                        '**',
                        '!node_modules/**',
                        '!dist/**',
                        '!.git/**',
                        '!.gitignore',
                        '!Gruntfile.js',
                        '!gruntfile.js',
                        '!package.json',
                        '!package-lock.json',
                        '!composer.json',
                        '!composer.lock',
                        '!phpcs.xml',
                        '!phpcs.xml.dist',
                        '!.phpcs.xml',
                        '!phpunit.xml',
                        '!phpunit.xml.dist',
                        '!tests/**',
                        '!bin/**',
                        '!*.md',
                        '!docs/**',
                        '!*.map',
                        '!*.log',
                        '!*.sql',
                        '!.DS_Store',
                        '!Thumbs.db',
                        '!.project',
                        '!.idea/**',
                        '!.vscode/**',
                        '!vendor/**',
                        '!*.swp',
                        '!*.swo',
                        '!*~'
                    ],
                    dest: 'dist/buddypress-birthdays/'
                }]
            }
        },

        // Create zip file
        compress: {
            dist: {
                options: {
                    archive: 'dist/buddypress-birthdays-<%= pkg.version %>.zip',
                    mode: 'zip'
                },
                files: [{
                    expand: true,
                    cwd: 'dist/',
                    src: ['buddypress-birthdays/**'],
                    dest: ''
                }]
            }
        },

        // Check text domain
        checktextdomain: {
            options: {
                text_domain: ['buddypress-birthdays'],
                keywords: [
                    '__:1,2d',
                    '_e:1,2d',
                    '_x:1,2c,3d',
                    'esc_html__:1,2d',
                    'esc_html_e:1,2d',
                    'esc_html_x:1,2c,3d',
                    'esc_attr__:1,2d',
                    'esc_attr_e:1,2d',
                    'esc_attr_x:1,2c,3d',
                    '_ex:1,2c,3d',
                    '_n:1,2,4d',
                    '_nx:1,2,4c,5d',
                    '_n_noop:1,2,3d',
                    '_nx_noop:1,2,3c,4d'
                ]
            },
            target: {
                files: [{
                    src: [
                        '*.php',
                        '**/*.php',
                        '!node_modules/**',
                        '!vendor/**',
                        '!tests/**',
                        '!dist/**'
                    ],
                    expand: true
                }]
            }
        },

        // CSS minification
        cssmin: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'assets/css/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'assets/css/',
                    ext: '.min.css'
                }]
            }
        },

        // JavaScript minification
        uglify: {
            options: {
                mangle: false
            },
            dist: {
                files: [{
                    expand: true,
                    cwd: 'assets/js/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'assets/js/',
                    ext: '.min.js'
                }]
            }
        },

        // Make POT file
        makepot: {
            target: {
                options: {
                    cwd: '.',
                    domainPath: 'languages/',
                    exclude: ['node_modules/*', 'vendor/*', 'dist/*', 'tests/*'],
                    mainFile: 'buddypress-birthdays.php',
                    potFilename: 'buddypress-birthdays.pot',
                    potHeaders: {
                        poedit: true,
                        'Last-Translator': 'Varun Dubey',
                        'Language-Team': 'Wbcom Designs',
                        'report-msgid-bugs-to': '',
                        'x-poedit-keywordslist': true
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true
                }
            }
        },

        // Shell commands
        shell: {
            makepot: {
                command: 'wp i18n make-pot . languages/buddypress-birthdays.pot --exclude=node_modules,dist,tests,vendor'
            }
        }
    });

    // Load tasks
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-checktextdomain');
    grunt.loadNpmTasks('grunt-shell');

    // Register tasks
    grunt.registerTask('minify', ['cssmin', 'uglify']);
    grunt.registerTask('i18n', ['checktextdomain', 'makepot']);
    grunt.registerTask('build', ['checktextdomain', 'minify', 'makepot']);
    grunt.registerTask('zip', ['clean:dist', 'build', 'copy:dist', 'compress:dist', 'clean:temp']);
    grunt.registerTask('default', ['checktextdomain', 'makepot']);
};
