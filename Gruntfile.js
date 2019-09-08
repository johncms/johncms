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

    // Обновление Dev Dependencies
    grunt.registerTask('upd', [
        'devUpdate:main'
    ]);
};
