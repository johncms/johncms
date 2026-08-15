<?php

// Default configuration
return [
    'johncms' => [
        'active'                  => 1,
        'antiflood'               => [
            'mode'    => 2,
            'day'     => 10,
            'night'   => 30,
            'dayfrom' => 10,
            'dayto'   => 22,
        ],
        'clean_time'              => 0,
        'copyright'               => 'JohnCMS',
        'email'                   => 'no-reply@example.com',
        'flsz'                    => 1000,
        'gzip'                    => 1,
        'homeurl'                 => 'http://localhost',
        'privacy_policy_url'      => '',
        'terms_of_use_url'        => '',
        'personal_data_policy_url' => '',
        'cookie_policy_url'       => '',
        'cookie_banner_enabled'   => 0,
        'cookie_banner_version'   => 1,
        'cookie_banner_text'      => [],
        'karma'                   => [
            'karma_points' => 5,
            'karma_time'   => 86400,
            'forum'        => 10,
            'time'         => 0,
            'on'           => 1,
            'adm'          => 0,
        ],
        'lng'                     => 'ru',
        'lng_list'                => [
            'en' => [
                'name'    => 'English',
                'version' => 1.0,
            ],
        ],
        // Whether a new account waits for an administrator to confirm it. Null means the site has
        // not decided since the setting was split off mod_reg, and the old number still answers.
        'registration_moderation' => null,
        'mod_reg'                 => 2,
        'mod_forum'               => 2,
        'mod_guest'               => 2,
        'mod_lib'                 => 2,
        'mod_lib_comm'            => true,
        'mod_down'                => 2,
        'mod_down_comm'           => true,
        'meta_key'                => 'johncms',
        'meta_desc'               => 'Powered by JohnCMS http://johncms.com',
        'skindef'                 => 'default',
        'timeshift'               => 3,
        'meta_title'              => 'JohnCMS',
        'user_email_required'     => 1,
        'user_email_confirmation' => 1,
    ],
];
