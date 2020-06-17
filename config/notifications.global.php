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
                'message' => static function ($fields = []) {
                    return d__('system', 'New answer in the topic:') .
                        ' <a href="' . $fields['topic_url'] . '"><b>' . $fields['topic_name'] . '</b></a><br>' .
                        d__('system', '<a href="%s">User <b>%s</b> responded to Your message</a>.', $fields['reply_to_message'], $fields['user_name']) .
                        '<div class="text-muted small mt-1">' . $fields['message'] . '</div>';
                },
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
