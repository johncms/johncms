<?php

// Default configuration
return [
    'johncms' => [
        'home_url'             => 'http://localhost',
        'terms_of_service_url' => '/info/tos',
        'privacy_policy_url'   => '/info/policy',
        'cookie_policy_url'    => '/info/cookie',
        'debug_bar_url'        => '/_debugbar/open/',

        'meta_title'       => 'JohnCMS',
        'meta_keywords'    => 'johncms',
        'meta_description' => 'Powered by JohnCMS https://johncms.com',

        'email'     => 'no-reply@example.com',
        'copyright' => 'JohnCMS',

        'timezone'   => 'UTC',
        'language'   => 'en',
        'perPage'    => 10,

        // User model settings
        'user_model' => [
            'casts'      => [],
            'fillable'   => [],
            'attributes' => [],
            'dates'      => [],
        ],

        // Settings for users
        'users'      => [
            'login_field' => 'login', // login|phone|email - the field which using for authorization.
        ],

        'antiflood' => [
            'mode'     => 2,
            'day'      => 10,
            'night'    => 30,
            'day_from' => 10,
            'day_to'   => 22,
        ],

        'avatar_colors' => [
            '#3F51B5',
            '#009688',
            '#9C27B0',
            '#F44336',
            '#FF4081',
            '#673AB7',
            '#2196F3',
            '#03A9F4',
            '#00BCD4',
            '#4CAF50',
            '#8BC34A',
            '#CDDC39',
            '#FFC107',
            '#FF9800',
            '#FF5722',
            '#795548',
            '#9E9E9E',
            '#607D8B',
        ],


        // need to clean
        'active'        => 1,
        'clean_time'    => 0,
        'flsz'          => 1000,
        'gzip'          => 1,
        'homeurl'       => 'http://localhost', // TODO: delete
        'karma'         => [
            'karma_points' => 5,
            'karma_time'   => 86400,
            'forum'        => 10,
            'time'         => 0,
            'on'           => 1,
            'adm'          => 0,
        ],
        'lng'           => 'ru',
        'lng_list'      => [
            'en' => [
                'name'    => 'English',
                'version' => 1.0,
            ],
        ],
        'mod_reg'       => 2,
        'mod_forum'     => 2,
        'mod_guest'     => 2,
        'mod_lib'       => 2,
        'mod_lib_comm'  => true,
        'mod_down'      => 2,
        'mod_down_comm' => true,
        'skindef'       => 'default',
        'timeshift'     => 3,
    ],
];
