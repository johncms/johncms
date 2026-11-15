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
                    if (! empty($fields['post_id']) && ! empty($fields['topic_id'])) {
                        $post_page = \Johncms\Modules\Forum\Application\ForumUtils::getPostPage((int) $fields['post_id'], (int) $fields['topic_id']);
                        $post_page .= '#post_' . $fields['post_id'];
                    } else {
                        $post_page = '/forum/';
                    }

                    return d__('system', '<a href="%s">User <b>%s</b> responded to your message in the topic %s</a>', $post_page, $fields['user_name'], $fields['topic_name']) .
                        '<div class="text-muted small mt-1">' . $fields['message'] . '</div>';
                },
            ],
        ],
    ],
    // The fields of an answer are stored as plain text and escaped here.
    'comments' => [
        'name'   => d__('system', 'Comments'),
        'events' => [
            'reply' => [
                'name'    => d__('system', 'An answer to your comment'),
                'message' => static function (array $fields = []): string {
                    $escape = static fn(string $key): string => htmlspecialchars((string) ($fields[$key] ?? ''), ENT_QUOTES, 'UTF-8');

                    return '<a href="' . $escape('reply_url') . '">'
                        . sprintf(d__('system', '<b>%s</b> answered your comment: %s'), $escape('user_name'), $escape('subject_title'))
                        . '</a>'
                        . ($escape('message') === '' ? '' : '<div class="text-muted small mt-1">' . $escape('message') . '</div>');
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
