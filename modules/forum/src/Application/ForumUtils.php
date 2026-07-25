<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumSectionTreeService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

class ForumUtils
{
    /**
     * Building breadcrumbs
     *
     * @param int $parent
     * @param string $current_item_name
     * @param string $current_item_url
     */
    public static function buildBreadcrumbs(int $parent = 0, string $current_item_name = '', string $current_item_url = ''): void
    {
        /** @var NavChain $nav_chain */
        $nav_chain = di(NavChain::class);

        foreach (di(ForumSectionTreeService::class)->getAncestors($parent) as $section) {
            $nav_chain->add($section->name, $section->url);
        }

        if (! empty($current_item_name)) {
            $nav_chain->add($current_item_name, $current_item_url);
        }
    }

    /**
     * Page not found
     */
    public static function notFound(): void
    {
        checkRedirect();
        $view = di(Render::class);

        if (! headers_sent()) {
            header('HTTP/1.0 404 Not Found');
        }

        echo $view->render(
            'system::pages/result',
            [
                'title'    => __('Forum'),
                'type'     => 'alert-danger',
                'message'  => __('Topic has been deleted or does not exists'),
                'back_url' => '/forum/',
            ]
        );
        exit;
    }

    /**
     * Replaces the URL to a bb-code with name of the topic.
     *
     * @param string $message
     * @return string
     */
    public static function topicLink(string $message): string
    {
        $message = preg_replace_callback(
            '~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            static function ($link) {
                if (! isset($link[3])) {
                    return '[url=' . $link[1] . ']' . $link[2] . '[/url]';
                }
                $parsed_url = parse_url($link[3]);

                /** @var Request $env */
                $request = di(Request::class);
                $host = $request->server->getString('HTTP_HOST', '');

                parse_str($parsed_url['query'] ?? '', $query_params);

                if ($parsed_url['host'] === $host && ! empty($query_params['id']) && ! empty($query_params['type']) && $query_params['type'] === 'topic') {
                    try {
                        $topic = (new ForumTopic())->findOrFail($query_params['id']);
                        $name = strtr(
                            $topic->name,
                            [
                                '&quot;' => '',
                                '&amp;'  => '',
                                '&lt;'   => '',
                                '&gt;'   => '',
                                '&#039;' => '',
                                '['      => '',
                                ']'      => '',
                            ]
                        );
                        $name = mb_strimwidth($name, 0, 60, '...');
                        return '[url=' . $link[3] . ']' . $name . '[/url]';
                    } catch (ModelNotFoundException $exception) {
                        return $link[3];
                    }
                }

                return $link[3];
            },
            $message
        );

        return $message;
    }

    /**
     * The method returns a link to the page with the post.
     *
     * @param int $post_id
     * @param int $topic_id
     * @return string
     */
    public static function getPostPage(int $post_id, int $topic_id): string
    {
        /** @var User $user */
        $user = di(User::class);
        $upfp = $user->set_forum['upfp'] ?? 0;
        $perPage = max(1, (int) $user->config->kmess);
        $totalMessages = (new ForumMessage())
            ->where('topic_id', '=', $topic_id)
            ->where('forum_messages.id', empty($upfp) ? '<=' : '>=', $post_id)
            ->orderBy('id', empty($upfp) ? 'ASC' : 'DESC')
            ->count();

        $page = max(1, (int) ceil($totalMessages / $perPage));
        $topicUrl = di(ForumTopicPathService::class)->getTopicUrlById($topic_id, $page > 1 ? $page : null);

        return $topicUrl ?? '/forum/';
    }
}
