module.exports = function (grunt) {
    require('time-grunt')(grunt);
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Копируем файлы из исходников
        copy: {
            distributive: {
                files: [
                    {
                        expand: true,
                        src: [
                            '**/**',
                            '.htaccess',
                            'files/.htaccess',

                            '!files/cache/**/*',
                            'files/cache/.htaccess',
                            '!files/downloads/files/**/*',
                            'files/downloads/files/index.php',

                            '!system/config/database.local.php',
                            '!system/config/system.local.php',

                            '!system/vendor/container-interop/container-interop/docs/**',
                            '!system/vendor/container-interop/container-interop/composer.json',

                            '!system/vendor/erusev/parsedown/test/**',
                            '!system/vendor/erusev/parsedown/composer.json',
                            '!system/vendor/erusev/parsedown/phpunit.xml.dist',

                            '!system/vendor/geshi/geshi/src/geshi/*',
                            'system/vendor/geshi/geshi/src/geshi/css.php',
                            'system/vendor/geshi/geshi/src/geshi/html5.php',
                            'system/vendor/geshi/geshi/src/geshi/javascript.php',
                            'system/vendor/geshi/geshi/src/geshi/php.php',
                            'system/vendor/geshi/geshi/src/geshi/sql.php',
                            'system/vendor/geshi/geshi/src/geshi/xml.php',
                            '!system/vendor/geshi/geshi/src/contrib/**',
                            '!system/vendor/geshi/geshi/src/docs/**',
                            '!system/vendor/geshi/geshi/build.properties.dist',
                            '!system/vendor/geshi/geshi/build.xml',
                            '!system/vendor/geshi/geshi/composer.json',

                            '!system/vendor/verot/class.upload.php/test/**',
                            '!system/vendor/verot/class.upload.php/composer.json',

                            '!system/vendor/zendframework/zend-i18n/doc/**',
                            '!system/vendor/zendframework/zend-i18n/CHANGELOG.md',
                            '!system/vendor/zendframework/zend-i18n/composer.json',
                            '!system/vendor/zendframework/zend-i18n/CONDUCT.md',
                            '!system/vendor/zendframework/zend-i18n/CONTRIBUTING.md',
                            '!system/vendor/zendframework/zend-i18n/mkdocs.yml',

                            '!system/vendor/zendframework/zend-servicemanager/benchmarks/**',
                            '!system/vendor/zendframework/zend-servicemanager/doc/**',
                            '!system/vendor/zendframework/zend-servicemanager/CHANGELOG.md',
                            '!system/vendor/zendframework/zend-servicemanager/CONDUCT.md',
                            '!system/vendor/zendframework/zend-servicemanager/CONTRIBUTING.md',
                            '!system/vendor/zendframework/zend-servicemanager/composer.json',
                            '!system/vendor/zendframework/zend-servicemanager/phpbench.json',
                            '!system/vendor/zendframework/zend-servicemanager/phpcs.xml',
                            '!system/vendor/zendframework/zend-servicemanager/mkdocs.yml',

                            '!system/vendor/zendframework/zend-stdlib/benchmarks/**',
                            '!system/vendor/zendframework/zend-stdlib/doc/**',
                            '!system/vendor/zendframework/zend-stdlib/CHANGELOG.md',
                            '!system/vendor/zendframework/zend-stdlib/CONDUCT.md',
                            '!system/vendor/zendframework/zend-stdlib/CONTRIBUTING.md',
                            '!system/vendor/zendframework/zend-stdlib/composer.json',
                            '!system/vendor/zendframework/zend-stdlib/mkdocs.yml',
                            '!system/vendor/zendframework/zend-stdlib/phpcs.xml',

                            '!dist/**',
                            '!distributive/**',
                            '!node_modules/**',
                            '!Gruntfile.js',
                            '!package.json',
                            '!composer.*'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_id: {
                files: [
                    {
                        expand: true,
                        src: [
                            'admin/locale/id/**',
                            'album/locale/id/**',
                            'downloads/locale/id/**',
                            'forum/locale/id/**',
                            'guestbook/locale/id/**',
                            'help/locale/id/**',
                            'library/locale/id/**',
                            'mail/locale/id/**',
                            'news/locale/id/**',
                            'profile/locale/id/**',
                            'registration/locale/id/**',
                            'system/locale/id/**',
                            'users/locale/id/**'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_pl: {
                files: [
                    {
                        expand: true,
                        src: [
                            'admin/locale/pl/**',
                            'album/locale/pl/**',
                            'downloads/locale/pl/**',
                            'forum/locale/pl/**',
                            'guestbook/locale/pl/**',
                            'help/locale/pl/**',
                            'library/locale/pl/**',
                            'mail/locale/pl/**',
                            'news/locale/pl/**',
                            'profile/locale/pl/**',
                            'registration/locale/pl/**',
                            'system/locale/pl/**',
                            'users/locale/pl/**'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_ru: {
                files: [
                    {
                        expand: true,
                        src: [
                            'admin/locale/ru/**',
                            'album/locale/ru/**',
                            'downloads/locale/ru/**',
                            'forum/locale/ru/**',
                            'guestbook/locale/ru/**',
                            'help/locale/ru/**',
                            'library/locale/ru/**',
                            'mail/locale/ru/**',
                            'news/locale/ru/**',
                            'profile/locale/ru/**',
                            'registration/locale/ru/**',
                            'system/locale/ru/**',
                            'users/locale/ru/**'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_vi: {
                files: [
                    {
                        expand: true,
                        src: [
                            'admin/locale/vi/**',
                            'album/locale/vi/**',
                            'downloads/locale/vi/**',
                            'forum/locale/vi/**',
                            'guestbook/locale/vi/**',
                            'help/locale/vi/**',
                            'library/locale/vi/**',
                            'mail/locale/vi/**',
                            'news/locale/vi/**',
                            'profile/locale/vi/**',
                            'registration/locale/vi/**',
                            'system/locale/vi/**',
                            'users/locale/vi/**'
                        ],
                        dest: 'distributive/'
                    }
                ]
            }
        },

        // Очищаем папки и удаляем файлы
        clean: {
            dist: ['dist'],
            distributive: ['distributive']
        },

        // Сжимаем файлы
        compress: {
            dist: {
                options: {
                    archive: 'dist/johncms-<%= pkg.version %>.zip'
                },

                files: [
                    {
                        expand: true,
                        dot: true,
                        cwd: 'distributive/',
                        src: ['**']
                    }
                ]
            },
            lng_id: {
                options: {
                    archive: 'dist/locales/id.zip'
                },

                files: [
                    {
                        expand: true,
                        dot: true,
                        cwd: 'distributive/',
                        src: ['**']
                    }
                ]
            },
            lng_pl: {
                options: {
                    archive: 'dist/locales/pl.zip'
                },

                files: [
                    {
                        expand: true,
                        dot: true,
                        cwd: 'distributive/',
                        src: ['**']
                    }
                ]
            },
            lng_ru: {
                options: {
                    archive: 'dist/locales/ru.zip'
                },

                files: [
                    {
                        expand: true,
                        dot: true,
                        cwd: 'distributive/',
                        src: ['**']
                    }
                ]
            },
            lng_vi: {
                options: {
                    archive: 'dist/locales/vi.zip'
                },

                files: [
                    {
                        expand: true,
                        dot: true,
                        cwd: 'distributive/',
                        src: ['**']
                    }
                ]
            }
        },

        // Обновляем зависимости
        devUpdate: {
            main: {
                options: {
                    updateType: 'force',
                    semver: false
                }
            }
        }
    });

    // Загружаем нужные модули
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-dev-update');

    // Общая задача
    grunt.registerTask('default', []);

    grunt.registerTask('distributive', [
        'clean:dist',
        'copy:distributive',
        'compress:dist',
        'clean:distributive'
    ]);

    grunt.registerTask('locales', [
        'clean:dist',

        'copy:lng_id',
        'compress:lng_id',
        'clean:distributive',

        'copy:lng_pl',
        'compress:lng_pl',
        'clean:distributive',

        'copy:lng_ru',
        'compress:lng_ru',
        'clean:distributive',

        'copy:lng_vi',
        'compress:lng_vi',
        'clean:distributive'
    ]);

    // Обновление Dev Dependencies
    grunt.registerTask('upd', [
        'devUpdate:main'
    ]);
};
