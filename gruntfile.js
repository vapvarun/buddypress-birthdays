'use strict';
module.exports = function (grunt) {

    // Load all grunt tasks
    require('load-grunt-tasks')(grunt);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

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
            assets: {
                files: [{
                    expand: true,
                    cwd: 'assets/css/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'assets/css/',
                    ext: '.min.css'
                }]
            },
            admin: {
                files: [{
                    expand: true,
                    cwd: 'admin/css/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'admin/css/',
                    ext: '.min.css'
                }]
            }
        },

        // JavaScript minification
        uglify: {
            options: {
                mangle: false
            },
            assets: {
                files: [{
                    expand: true,
                    cwd: 'assets/js/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'assets/js/',
                    ext: '.min.js'
                }]
            },
            admin: {
                files: [{
                    expand: true,
                    cwd: 'admin/js/',
                    src: ['*.js', '!*.min.js'],
                    dest: 'admin/js/',
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
    grunt.loadNpmTasks('grunt-checktextdomain');
    grunt.loadNpmTasks('grunt-shell');

    // Register tasks. The release zip is built by bin/build-release.sh only (folder = WordPress.org slug).
    grunt.registerTask('minify', ['cssmin:assets', 'cssmin:admin', 'uglify:assets', 'uglify:admin']);
    grunt.registerTask('i18n', ['checktextdomain', 'makepot']);
    grunt.registerTask('build', ['checktextdomain', 'minify']);
    grunt.registerTask('default', ['checktextdomain', 'makepot']);
};
