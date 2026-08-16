<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

return [
    'mail' => [
        /*
         * The mailer DSN. When it is set, everything below is ignored.
         *
         * This is the shortest way to reach any transport the mailer supports:
         *
         *   smtp://user:pass@smtp.example.com:587   STARTTLS is negotiated when offered
         *   smtps://user:pass@smtp.example.com:465  TLS from the first byte
         *   sendmail://default                      the sendmail binary of the server
         *   native://default                        the settings of php.ini
         *   null://null                             discards everything, for a dev stand
         *
         * A provider with its own API (Mailgun, Postmark, SES, Brevo, ...) needs its bridge
         * package installed, and is then addressed by the DSN of that bridge. Several transports
         * can be combined: `failover://` switches to the next one when a transport is down,
         * `roundrobin://` spreads the messages over all of them.
         *
         * Useful smtp parameters: ?verify_peer=0 for a self-signed certificate,
         * ?local_domain=example.com when the server requires a specific HELO name.
         */
        'dsn'       => '',

        // The settings below build a DSN when the option above is empty.

        // Transport: smtp, sendmail, native or null
        'transport' => 'sendmail',

        // Transport settings
        'options'   => [
            'smtp' => [
                'host'       => '127.0.0.1',
                'username'   => 'mail@example.com',
                'password'   => 'password',
                'port'       => 465,
                // ssl - TLS from the first byte (port 465), tls - STARTTLS, empty - no encryption
                'encryption' => 'ssl',
            ],
        ],
    ],
];
