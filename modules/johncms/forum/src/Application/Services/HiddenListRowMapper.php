<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;
use Johncms\Users\User;

/**
 * The rows of the list of the hidden content of the forum, as forum::admin/hidden draws them: who
 * wrote it and where, who hid it, and the links that narrow the list to the same author, topic or
 * section.
 *
 * The text of a post is a plain excerpt, its markup taken out; the template escapes it. The
 * addresses come from the cast of the model as text already.
 */
final readonly class HiddenListRowMapper
{
    private const int EXCERPT = 300;

    public function __construct(
        private HiddenListFilterMapper $filterMapper,
        private ForumTopicPathService $topicPath,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * @param Collection<int, ForumMessage> $posts
     * @return list<array<string, mixed>>
     */
    public function posts(Collection $posts, HiddenListFilter $filter): array
    {
        // The address and the user agent of a visitor are for the staff only.
        $origin = $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW);
        $rows = [];

        foreach ($posts as $post) {
            $topic = $post->topic;

            $rows[] = [
                'id'         => $post->id,
                'author'     => $this->author((int) $post->user_id, (string) $post->user_name, $post->user),
                'date'       => (int) $post->date,
                'topic_name' => $topic->name ?? '',
                'topic_url'  => $topic !== null ? $this->topicPath->getTopicUrl($topic) : '',
                'excerpt'    => $this->excerpt((string) $post->text),
                'hidden_by'  => (string) $post->deleted_by,
                'origin'     => $origin ? $this->origin((string) $post->ip, (string) $post->ip_via_proxy, (string) $post->user_agent) : null,
                'actions'    => [
                    $this->action($post->url, __('Open in the topic'), 'external-link'),
                    $this->action($this->filterMapper->url($filter->withAuthor((int) $post->user_id)), __('Only this author'), 'user'),
                    $this->action($this->filterMapper->url($filter->withTopic((int) $post->topic_id)), __('Only this topic'), 'message-square'),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @param Collection<int, ForumTopic> $topics
     * @return list<array<string, mixed>>
     */
    public function topics(Collection $topics, HiddenListFilter $filter): array
    {
        $rows = [];

        foreach ($topics as $topic) {
            $url = $this->topicPath->getTopicUrl($topic);

            $rows[] = [
                'id'           => $topic->id,
                'name'         => $topic->name,
                'url'          => $url,
                'section_name' => $topic->section->name ?? '',
                'author'       => $this->author((int) $topic->user_id, (string) $topic->user_name, null),
                'posts'        => (int) $topic->mod_post_count,
                'date'         => (int) $topic->mod_last_post_date,
                'hidden_by'    => (string) $topic->deleted_by,
                'actions'      => [
                    $this->action($url, __('Open on the site'), 'external-link'),
                    $this->action($this->filterMapper->url($filter->withAuthor((int) $topic->user_id)), __('Only this author'), 'user'),
                    $this->action($this->filterMapper->url($filter->withSection((int) $topic->section_id)), __('Only this section'), 'folder'),
                ],
            ];
        }

        return $rows;
    }

    /**
     * @return array{id: int, name: string, url: string, online: bool}
     */
    private function author(int $id, string $name, ?User $user): array
    {
        return [
            'id'     => $id,
            'name'   => $name,
            'url'    => $id > 0 ? '/admin/users/' . $id : '',
            'online' => $user !== null && time() <= $user->lastdate + 300,
        ];
    }

    private function excerpt(string $text): string
    {
        // A post is kept as the HTML of the editor or, from older versions, as the BB codes.
        $plain = html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = trim((string) preg_replace(['~\[/?[a-z*]+(?:=[^\]]*)?\]~i', "~\n{3,}~"], ['', "\n\n"], $plain));

        return mb_strlen($plain) > self::EXCERPT ? rtrim(mb_substr($plain, 0, self::EXCERPT)) . '…' : $plain;
    }

    /**
     * @return array{ip: string, ip_url: string, proxy: string, proxy_url: string, browser: string}
     */
    private function origin(string $ip, string $proxy, string $browser): array
    {
        return [
            'ip'        => $ip,
            'ip_url'    => '/admin/ip-search?ip=' . rawurlencode($ip),
            'proxy'     => $proxy,
            'proxy_url' => $proxy !== '' ? '/admin/ip-search?ip=' . rawurlencode($proxy) : '',
            'browser'   => $browser,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function action(string $url, string $title, string $icon): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => false, 'dialog' => false, 'fields' => []];
    }
}
