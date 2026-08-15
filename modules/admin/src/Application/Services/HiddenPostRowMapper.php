<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;
use Twig\Markup;

final readonly class HiddenPostRowMapper
{
    private const BASE = '/admin/forum/hidden-posts';

    public function __construct(
        private DateFormatterInterface $dateFormatter,
        private ForumTopicPathService $topicPath,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    /**
     * @param iterable<ForumMessage> $messages
     * @return array<int, array<string, mixed>>
     */
    public function mapMany(iterable $messages): array
    {
        $rows = [];
        foreach ($messages as $message) {
            $rows[] = $this->map($message);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function map(ForumMessage $message): array
    {
        $author = $message->user_id ? User::query()->find($message->user_id) : null;
        $topic = ForumTopic::query()->find($message->topic_id);

        $text = PlainTextFormatter::toHtml(mb_substr($message->text, 0, 500));
        $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);

        return [
            'id'                      => $message->user_id,
            'user_name'               => $message->user_name,
            'user_profile_link'       => $this->profileLink($message->user_id),
            'display_date'            => $this->dateFormatter->format($message->date),
            'topic_name'              => $topic->name ?? '',
            'topic_url'               => $topic !== null ? ($this->topicPath->getTopicUrlById((int) $topic->id) ?? '/forum/') : '/forum/',
            'formatted_text'          => new Markup((string) $text, 'UTF-8'),
            // The address and the user agent of a visitor are for the staff only.
            'show_origin'             => $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW),
            'browser'                 => $message->user_agent,
            'user_is_online'          => $author !== null && time() <= $author->lastdate + 300,
            'ip'                      => long2ip((int) $message->ip),
            'ip_via_proxy'            => $message->ip_via_proxy ? long2ip((int) $message->ip_via_proxy) : 0,
            'search_ip_url'           => '/admin/ip-search?ip=' . long2ip((int) $message->ip),
            'search_ip_via_proxy_url' => '/admin/ip-search?ip=' . ($message->ip_via_proxy ? long2ip((int) $message->ip_via_proxy) : ''),
            'deleted'                 => $message->deleted,
            'deleted_by'              => $message->deleted_by,
            'edit_count'              => $message->edit_count,
            'editor_name'             => $message->editor_name,
            'edit_time'               => $message->edit_time ? $this->dateFormatter->format($message->edit_time) : '',
            'buttons'                 => [
                ['url' => self::BASE . '?tsort=' . $message->topic_id, 'name' => __('by topic')],
                ['url' => self::BASE . '?usort=' . $message->user_id, 'name' => __('by author')],
            ],
        ];
    }

    private function profileLink(?int $userId): string
    {
        return $userId && $this->currentUser->id() !== $userId ? '/profile/' . $userId : '';
    }
}
