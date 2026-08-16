<?php

declare(strict_types=1);

// Authentication and authorization defaults. Override in auth.local.php — that is also the only
// place where secrets (client keys of the external login providers) may be written.
return [
    'auth' => [
        'session' => [
            // The cookie holding the session secret.
            'cookie_name' => 'jc_auth',

            // Length of the secret in bytes, before base64url encoding.
            'token_bytes' => 32,

            // Sliding lifetime of a "remember me" session, in seconds. Counted from the last
            // visit, not from the sign-in: coming back on day 14 extends it by another 30 days.
            'lifetime' => 30 * 24 * 60 * 60,

            // Lifetime of a session started without "remember me", in seconds. The cookie is a
            // session cookie on top of this, but the server-side limit is what actually applies:
            // browsers restore session cookies when "continue where you left off" is enabled.
            'idle_lifetime' => 12 * 60 * 60,

            // How old the sliding extension may get before it is written again. Extending on
            // every request would mean a database write per request; five minutes of drift is
            // nothing against a 30-day window.
            'renew_interval' => 5 * 60,

            // Hard cap that is never extended, in seconds. Off by default: it would undo the
            // point of a sliding lifetime by signing active visitors out every few months.
            'absolute_lifetime' => null,

            // Whether "remember me" is pre-checked on the sign-in forms. Cookies used to last a
            // year unconditionally, so leaving it off by default would read as a regression.
            'remember_by_default' => true,
        ],

        'impersonation' => [
            // The cookie holding the administrator's own session while they browse as somebody
            // else. Their session is not closed and not overwritten — it waits here.
            'parent_cookie_name' => 'jc_auth_parent',

            // How long browsing as somebody else lasts, in seconds. Never extended, unlike an
            // ordinary session: an administrator who keeps clicking would otherwise stay somebody
            // else indefinitely. Starting again is one click and one more line in the audit trail.
            'lifetime' => 3600,

            // What is refused while browsing as somebody else, whatever the roles say. Taking
            // over the account outright — changing its password or address, deleting it — is not
            // what impersonation is for, and entering the panel as somebody else has no purpose
            // beyond hiding who acted.
            'denied_permissions' => [
                'users.impersonate',
                'admin.access',
                'admin.settings.manage',
                'admin.roles.manage',
            ],
        ],

        'password' => [
            // Any algorithm constant password_hash() accepts. PASSWORD_DEFAULT follows the PHP
            // release; pin it to PASSWORD_ARGON2ID where the extension is available.
            'algorithm' => PASSWORD_DEFAULT,

            // Passed to password_hash() as-is. Empty means the defaults of the algorithm.
            'options' => [],
        ],
    ],
];
