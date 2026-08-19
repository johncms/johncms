<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

// How visitors are told apart from bots, reached through Johncms\Captcha\CaptchaManager.
// Override in captcha.local.php — that file is not in the repository, so the settings of a
// server, keys of a remote service included, survive an update of the CMS.
return [
    'captcha' => [
        // The provider the forms use. A key nothing is registered under, or one whose settings
        // are incomplete, falls back to the built-in picture: a captcha that cannot work must
        // not turn into no captcha at all.
        //
        // Shipped: image, hcaptcha, smartcaptcha, recaptcha_v3. The three services need no
        // extra packages — only their keys, which are filled in from the admin panel.
        'default' => 'image',

        // Settings per provider, by the key the provider is registered under. A provider that a
        // module brought along reads its own from here as well; what the panel asks for comes
        // from its settingsFields().
        'providers' => [
            'image' => [
                'options' => [
                    'width'      => 190,
                    'height'     => 90,
                    // png, webp or gif. JPEG is not among them: it has no transparency.
                    'format'     => 'png',
                    'length_min' => 4,
                    'length_max' => 5,
                ],
            ],

            // A checkbox, with a puzzle behind it for whoever looks suspicious.
            'hcaptcha' => [
                'options' => [
                    'site_key'   => '',
                    'secret_key' => '',
                ],
            ],

            // Yandex names its keys the other way round: the client key goes on the page, the
            // server key checks the answer.
            'smartcaptcha' => [
                'options' => [
                    'site_key'   => '',
                    'secret_key' => '',
                ],
            ],

            // Shows the visitor nothing and scores them instead, so somebody scored below the
            // threshold is simply refused — there is nothing to offer them to prove otherwise.
            // It also loads scripts from Google on every page carrying a form.
            'recaptcha_v3' => [
                'options' => [
                    'site_key'        => '',
                    'secret_key'      => '',
                    'score_threshold' => 0.5,
                ],
            ],
        ],
    ],
];
