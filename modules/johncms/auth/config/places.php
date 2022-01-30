<?php

/**
 * In this file you can describe the places that will be displayed in the online module.
 * Example:
 * 'route.name' => 'Page name',
 * 'route.name' => function($params) { return 'Page name'; },
 */

return [
    'login.index'               => d__('auth', 'Login'),
    'login.authorize'           => d__('auth', 'Login'),
    'registration.index'        => d__('auth', 'Registration'),
    'registration.store'        => d__('auth', 'Registration'),
    'registration.closed'       => d__('auth', 'Registration'),
    'registration.confirmEmail' => d__('auth', 'Confirms the e-mail'),
    'logout.index'              => d__('auth', 'Logout'),
    'logout.confirm'            => d__('auth', 'Logout'),
];
