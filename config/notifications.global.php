<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    // Шаблоны уведомлений для модуля форума
    'forum' => [
        'name'   => d__('system', 'Forum'),
        'events' => [
            'new_message' => [
                'name'    => d__('system', 'New replay in forum'),
                'message' => d__('system', 'New answer in the topic: <a href="#topic_url#"><b>#topic_name#</b></a><br>User <b>#user_name#</b> responded to Your message'),
            ],
        ],
    ],
];
