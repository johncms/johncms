<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forum;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumSection;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Http\Request;
use Johncms\NavChain;
use Johncms\Users\User;
use Johncms\View\MetaTagManager;
use Johncms\View\Render;

class ForumUtils
{
    private NavChain $navChain;

    public function __construct(NavChain $navChain)
    {
        $this->navChain = $navChain;
    }

    /**
     * Building breadcrumbs
     *
     * @param int $parent
     * @param string $currentItemName
     * @param string $currentItemUrl
     */
    public function buildBreadcrumbs(int $parent = 0, string $currentItemName = '', string $currentItemUrl = ''): void
    {
        if ($parent) {
            $chain = $this->getSectionsChain($parent);
            foreach ($chain as $section) {
                $this->navChain->add($section->name, $section->url);
            }
        }

        if (! empty($currentItemName)) {
            $this->navChain->add($currentItemName, $currentItemUrl);
        }
    }

    /**
     * Set meta tags for the topic
     */
    public function setMetaForTopic(ForumTopic $topic): void
    {
        $request = di(Request::class);
        $metaTagManager = di(MetaTagManager::class);
        $metaTagManager->setTitle($topic->name);
        $metaTagManager->setPageTitle($topic->name);
        $metaTagManager->setDescription($topic->calculated_meta_description);
        $metaTagManager->setKeywords($topic->calculated_meta_keywords);

        // Setting the canonical URL
        $page = $request->getQuery('page', 0, FILTER_VALIDATE_INT);
        $canonical = config('johncms.homeurl') . $topic->url;
        if ($page > 1) {
            $canonical .= '?page=' . $page;
        }
        $metaTagManager->setCanonical($canonical);
    }

    /**
     * @param int $sectionId
     * @param array $sections
     * @return ForumSection[]
     */
    public function getSectionsChain(int $sectionId, array &$sections = []): array
    {
        $section = ForumSection::query()->find($sectionId);
        $sections[] = $section;
        if ($section->parent) {
            $this->getSectionsChain($section->parent, $sections);
        }
        krsort($sections);
        return $sections;
    }

    /**
     * Page not found
     *
     * @return no-return
     */
    public static function notFound()
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

                /** @var \Johncms\Http\Request $env */
                $request = di(Request::class);
                $host = $request->getServer('HTTP_HOST', '');

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
        $message = (new ForumMessage())
            ->where('topic_id', '=', $topic_id)
            ->where('forum_messages.id', empty($upfp) ? '<=' : '>=', $post_id)
            ->orderBy('id', empty($upfp) ? 'ASC' : 'DESC')
            ->paginate($user->config->kmess);

        $page = $message->lastPage();

        return '/forum/?type=topic&id=' . $topic_id . ($page > 1 ? '&page=' . $page : '');
    }

    // Вспомогательная Функция обработки ссылок форума
    public static function forumLink($m)
    {
        global $db, $config;

        if (! isset($m[3])) {
            return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
        }
        $p = parse_url($m[3]);

        if ('http://' . $p['host'] . ($p['path'] ?? '') . '?id=' == $config['homeurl'] . '/forum/?id=') {
            $thid = abs((int) (preg_replace('/(.*?)id=/si', '', $m[3])));
            $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id`= '${thid}' AND (`deleted` != '1' OR deleted IS NULL)");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $name = strtr(
                    $res['name'],
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

                if (mb_strlen($name) > 40) {
                    $name = mb_substr($name, 0, 40) . '...';
                }

                return '[url=' . $m[3] . ']' . $name . '[/url]';
            }

            return $m[3];
        }

        return $m[3];
    }

    public static function replaceKeywords($search, $text)
    {
        $search = str_replace('*', '', $search);

        return mb_strlen($search) < 3 ? $text : preg_replace(
            '|(' . preg_quote($search, '/') . ')|siu',
            '<span style="background-color: #FFFF33">$1</span>',
            $text
        );
    }
}
