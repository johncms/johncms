<?php

// Default configuration
return [
    'johncms' => [
        'home_url'             => 'http://localhost',
        'terms_of_service_url' => '/info/tos',
        'privacy_policy_url'   => '/info/policy',
        'cookie_policy_url'    => '/info/cookie',

        'meta_title'       => 'JohnCMS',
        'meta_keywords'    => 'johncms',
        'meta_description' => 'Powered by JohnCMS http://johncms.com',

        'email'         => 'no-reply@example.com',
        'copyright'     => 'JohnCMS',

        // User model settings
        'user_model'    => [
            'casts'      => [],
            'fillable'   => [],
            'attributes' => [],
            'dates'      => [],
        ],

        // Settings for users
        'users'         => [
            'login_field' => 'login', // login|phone|email - the field which using for authorization.
        ],


        // need to clean
        'active'        => 1,
        'antiflood'     => [
            'mode'    => 2,
            'day'     => 10,
            'night'   => 30,
            'dayfrom' => 10,
            'dayto'   => 22,
        ],
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
