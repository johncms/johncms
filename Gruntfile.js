/*!
 * mobiCMS http://mobicms.net
 */

module.exports = function (grunt) {
    require('time-grunt')(grunt);
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Очищаем папки и удаляем файлы
        clean: {
            dist: ['dist'],

            distributive: ['distributive'],

            sweep: [
                // Загрузки
                'distributive/download/arctemp/*',
                '!distributive/download/arctemp/index.php',
                'distributive/download/files/*',
                '!distributive/download/files/index.php',
                '!distributive/download/files/.htaccess',
                'distributive/download/screen/*',
                '!distributive/download/screen/index.php',
                // Файлы
                'distributive/files/cache/*',
                '!distributive/files/cache/.htaccess',
                'distributive/files/forum/attach/*',
                '!distributive/files/forum/attach/index.php',
                'distributive/files/forum/topics/*',
                '!distributive/files/forum/topics/index.php',
                'distributive/files/library/images/big/*',
                'distributive/files/library/images/orig/*',
                'distributive/files/library/images/small/*',
                'distributive/files/library/tmp/*',
                'distributive/files/lng_edit/*',
                '!distributive/files/lng_edit/index.php',
                'distributive/files/mail/*',
                '!distributive/files/mail/index.php',
                'distributive/files/users/album/*',
                '!distributive/files/users/album/index.php',
                'distributive/files/users/avatar/*',
                '!distributive/files/users/avatar/index.php',
                'distributive/files/users/photo/*',
                '!distributive/files/users/photo/index.php',
                'gallery/foto/*',
                '!gallery/foto/.htaccess',
                '!gallery/foto/index.php',
                'gallery/temp/*',
                '!gallery/temp/index.php',
                // Конфигурация
                'distributive/incfiles/db.php'
            ]
        },

        // Копируем файлы из исходников
        copy: {
            dist: {
                files: [
                    {
                        expand: true,
                        src: [
                            '**/**',
                            '.htaccess',
                            'incfiles/.htaccess',
                            'files/.htaccess',
                            'files/cache/.htaccess',
                            'download/files/.htaccess',
                            'gallery/foto/.htaccess',
                            '!**/node_modules/**',
                            '!**/distributive/**',
                            '!**/dist/**',
                            '!Gruntfile.js',
                            '!package.json'
                        ],
                        dest: 'distributive/'
                    },
                    {
                        expand: true,
                        src: ['.install/setup/*'],
                        dest: 'distributive/install/',
                        flatten: true
                    }
                ]
            },
            lng_az: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/az/*',
                            'images/flags/az.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_by: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/by/*',
                            'images/flags/by.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_cn: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/cn/*',
                            'images/flags/cn.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_de: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/de/*',
                            'images/flags/de.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_en: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/en/*',
                            'images/flags/en.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_fr: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/fr/*',
                            'images/flags/fr.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_ge: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/ge/*',
                            'images/flags/ge.gif'
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
                            'incfiles/languages/id/*',
                            'images/flags/id.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_kg: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/kg/*',
                            'images/flags/kg.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_kz: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/kz/*',
                            'images/flags/kz.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_lv: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/lv/*',
                            'images/flags/lv.gif'
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
                            'incfiles/languages/pl/*',
                            'images/flags/pl.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_ro: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/ro/*',
                            'images/flags/ro.gif'
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
                            'incfiles/languages/ru/*',
                            'images/flags/ru.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_sr: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/sr/*',
                            'images/flags/sr.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_tj: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/tj/*',
                            'images/flags/tj.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_uk: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/uk/*',
                            'images/flags/uk.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_uz: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/uz/*',
                            'images/flags/uz.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            },
            lng_vn: {
                files: [
                    {
                        expand: true,
                        src: [
                            'incfiles/languages/vn/*',
                            'images/flags/vn.gif'
                        ],
                        dest: 'distributive/'
                    }
                ]
            }
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
            lng_az: {
                options: {
                    archive: 'dist/languages/az.zip'
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
            lng_by: {
                options: {
                    archive: 'dist/languages/by.zip'
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
            lng_cn: {
                options: {
                    archive: 'dist/languages/cn.zip'
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
            lng_de: {
                options: {
                    archive: 'dist/languages/de.zip'
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
            lng_en: {
                options: {
                    archive: 'dist/languages/en.zip'
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
            lng_fr: {
                options: {
                    archive: 'dist/languages/fr.zip'
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
            lng_ge: {
                options: {
                    archive: 'dist/languages/ge.zip'
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
                    archive: 'dist/languages/id.zip'
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
            lng_kg: {
                options: {
                    archive: 'dist/languages/kg.zip'
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
            lng_kz: {
                options: {
                    archive: 'dist/languages/kz.zip'
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
            lng_lv: {
                options: {
                    archive: 'dist/languages/lv.zip'
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
                    archive: 'dist/languages/pl.zip'
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
            lng_ro: {
                options: {
                    archive: 'dist/languages/ro.zip'
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
                    archive: 'dist/languages/ru.zip'
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
            lng_sr: {
                options: {
                    archive: 'dist/languages/sr.zip'
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
            lng_tj: {
                options: {
                    archive: 'dist/languages/tj.zip'
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
            lng_uk: {
                options: {
                    archive: 'dist/languages/uk.zip'
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
            lng_uz: {
                options: {
                    archive: 'dist/languages/uz.zip'
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
            lng_vn: {
                options: {
                    archive: 'dist/languages/vn.zip'
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
    grunt.registerTask('default', [
        'clean:dist',
        'distributive',
        'languages'
    ]);

    // Сборка дистрибутива
    grunt.registerTask('distributive', [
        'copy:dist',
        'clean:sweep',
        'compress:dist',
        'clean:distributive'
    ]);

    // Сборка пакетов с языками
    grunt.registerTask('languages', [
        'copy:lng_az',
        'compress:lng_az',
        'clean:distributive',

        'copy:lng_by',
        'compress:lng_by',
        'clean:distributive',

        'copy:lng_cn',
        'compress:lng_cn',
        'clean:distributive',

        'copy:lng_de',
        'compress:lng_de',
        'clean:distributive',

        'copy:lng_en',
        'compress:lng_en',
        'clean:distributive',

        'copy:lng_fr',
        'compress:lng_fr',
        'clean:distributive',

        'copy:lng_ge',
        'compress:lng_ge',
        'clean:distributive',

        'copy:lng_id',
        'compress:lng_id',
        'clean:distributive',

        'copy:lng_kg',
        'compress:lng_kg',
        'clean:distributive',

        'copy:lng_kz',
        'compress:lng_kz',
        'clean:distributive',

        'copy:lng_lv',
        'compress:lng_lv',
        'clean:distributive',

        'copy:lng_pl',
        'compress:lng_pl',
        'clean:distributive',

        'copy:lng_ro',
        'compress:lng_ro',
        'clean:distributive',

        'copy:lng_ru',
        'compress:lng_ru',
        'clean:distributive',

        'copy:lng_sr',
        'compress:lng_sr',
        'clean:distributive',

        'copy:lng_tj',
        'compress:lng_tj',
        'clean:distributive',

        'copy:lng_uk',
        'compress:lng_uk',
        'clean:distributive',

        'copy:lng_uz',
        'compress:lng_uz',
        'clean:distributive',

        'copy:lng_vn',
        'compress:lng_vn',
        'clean:distributive'
    ]);

    // Обновление Dev Dependencies
    grunt.registerTask('upd', [
        'devUpdate:main'
    ]);
};
