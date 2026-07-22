<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;

final readonly class HiddenTopicRowMapper
{
    private const BASE = '/admin/forum/hidden-topics';

    public function __construct(
        private DateFormatterInterface $dateFormatter,
        private ForumTopicPathService $topicPath,
        private User $currentUser,
    ) {
    }

    /**
     * @param iterable<ForumTopic> $topics
     * @return array<int, array<string, mixed>>
     */
    public function mapMany(iterable $topics): array
    {
        $rows = [];
        foreach ($topics as $topic) {
            $rows[] = $this->map($topic);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function map(ForumTopic $topic): array
    {
        $author = $topic->user_id ? User::query()->find($topic->user_id) : null;

        $subcat = ForumSection::query()->find($topic->section_id);
        $cat = $subcat ? ForumSection::query()->find($subcat->parent) : null;
        $path = [];
        if ($cat !== null) {
            $path[] = ['url' => '/forum/?id=' . $cat->id, 'name' => $cat->name];
        }
        if ($subcat !== null) {
            $path[] = ['url' => '/forum/?type=topics&id=' . $subcat->id, 'name' => $subcat->name];
        }

        $ip = $author ? (int) $author->getRawOriginal('ip') : 0;
        $proxy = $author ? (int) $author->getRawOriginal('ip_via_proxy') : 0;

        return [
            'id'                      => $topic->user_id,
            'user_name'               => $topic->user_name,
            'user_profile_link'       => $this->profileLink($topic->user_id),
            'display_date'            => $this->dateFormatter->format($topic->mod_last_post_date),
            'topic_name'              => $topic->name,
            'topic_url'               => $this->topicPath->getTopicUrlById((int) $topic->id) ?? '/forum/',
            'path'                    => $path,
            'rights'                  => $author->rights ?? 0,
            'browser'                 => $author->browser ?? '',
            'user_is_online'          => $author !== null && time() <= $author->lastdate + 300,
            'ip'                      => long2ip($ip),
            'ip_via_proxy'            => $proxy ? long2ip($proxy) : 0,
            'search_ip_url'           => '/admin/ip-search?ip=' . long2ip($ip),
            'search_ip_via_proxy_url' => '/admin/ip-search?ip=' . ($proxy ? long2ip($proxy) : ''),
            'deleted'                 => $topic->deleted,
            'deleted_by'              => $topic->deleted_by,
            'buttons'                 => [
                ['url' => self::BASE . '?usort=' . $topic->user_id, 'name' => __('by author')],
                ['url' => self::BASE . '?rsort=' . $topic->section_id, 'name' => __('by section')],
            ],
        ];
    }

    private function profileLink(?int $userId): string
    {
        return $userId && $this->currentUser->id !== $userId ? '/profile/' . $userId : '';
    }
}
