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
                'name'    => d__('system', 'New reply on the forum'),
                'message' => d__('system', 'New answer in the topic:') .
                    ' <a href="#topic_url#"><b>#topic_name#</b></a><br>' .
                    d__('system', 'User <b>#user_name#</b> responded to <a href="#reply_to_message#">Your message</a>.') .
                    '<div class="text-muted small mt-2">#message#</div>',
            ],
        ],
    ],
    'karma' => [
        'name'   => d__('system', 'Karma'),
        'events' => [
            'new_vote' => [
                'name'    => d__('system', 'New vote in karma'),
                'message' => d__('system', 'User <b>#user_name#</b> voted in <a href="#karma_url#">your karma</a>.') .
                    '<div><b>#vote_points#</b> #message#</div>',
            ],
        ],
    ],
];
