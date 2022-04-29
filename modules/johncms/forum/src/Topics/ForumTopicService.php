<?php

declare(strict_types=1);

namespace Johncms\Forum\Topics;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumSection;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Models\ForumUnread;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Users\UserManager;

class ForumTopicService
{
    public ?User $user;
    public Request $request;

    public function __construct(?User $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }

    public function getUnread(): ?Builder
    {
        if (! $this->user) {
            return null;
        }

        return ForumTopic::query()
            ->select(['sect.name as section_name', 'forum.name as forum_name', 'forum.id as forum_id', 'forum_topic.*'])
            ->leftJoin('forum_read as rdm', function (JoinClause $joinClause) {
                return $joinClause->on('id', '=', 'rdm.topic_id')
                    ->where('rdm.user_id', $this->user->id);
            })
            ->leftJoin('forum_sections as sect', 'sect.id', '=', 'section_id')
            ->leftJoin('forum_sections as forum', 'forum.id', '=', 'sect.parent')
            ->when(! $this->user?->hasAnyRole(), function (Builder $builder) {
                return $builder->where('deleted', '<>', 1)->orWhereNull('deleted');
            })
            ->where(function (Builder $builder) {
                return $builder->whereNull('rdm.topic_id')->orWhere('last_post_date', '>', 'rdm.time');
            })
            ->orderByDesc('last_post_date');
    }

    public function getTopics(?int $sectionId = null): ?Builder
    {
        return ForumTopic::query()
            ->read()
            ->when($sectionId, function (Builder $builder) use ($sectionId) {
                return $builder->where('section_id', '=', $sectionId);
            })
            ->orderByDesc('pinned')
            ->orderByDesc('last_post_date');
    }

    /**
     * Marking a topic as read for a specific user
     */
    public function markAsRead(int $topicId, int $userId, ?int $time = null): void
    {
        ForumUnread::query()->updateOrInsert(['topic_id' => $topicId, 'user_id' => $userId], ['time' => $time ?? time()]);
    }

    /**
     * Increase view counter and mark topic as viewed for current user's session
     */
    public function markAsViewed(ForumTopic $forumTopic): void
    {
        $session = di(Session::class);
        // Increasing the number of views
        if (empty($session->get('viewed_topics')) || ! in_array($forumTopic->id, $session->get('viewed_topics', []))) {
            $forumTopic->update(['view_count' => $forumTopic->view_count + 1]);
            $viewed = $session->get('viewed_topics', []);
            $viewed[] = $forumTopic->id;
            $session->set('viewed_topics', $viewed);
        }
    }

    /**
     * @param ForumSection $forumSection
     * @param User $user
     * @param array{
     *     name: string,
     *     message: string,
     *     meta_keywords: string | null,
     *     meta_description: string | null
     * } $fields
     * @return array{topic: ForumTopic, message: ForumMessage}
     */
    public function createTopic(ForumSection $forumSection, User $user, array $fields): array
    {
        $topic = ForumTopic::query()->create(
            [
                'section_id'       => $forumSection->id,
                'created_at'       => Carbon::now(),
                'user_id'          => $this->user->id,
                'user_name'        => $this->user->display_name,
                'name'             => $fields['name'],
                'meta_keywords'    => $fields['meta_keywords'] ?? null,
                'meta_description' => $fields['meta_description'] ?? null,
                'last_post_date'   => time(),
                'post_count'       => 0,
                'curators'         => $forumSection->access === 1 ? [$this->user->id => $this->user->display_name] : [],
            ]
        );

        $message = (new ForumMessage())->create(
            [
                'topic_id'     => $topic->id,
                'date'         => time(),
                'user_id'      => $this->user->id,
                'user_name'    => $this->user->display_name,
                'ip'           => ip2long($this->request->getIp() ?? ''),
                'ip_via_proxy' => ip2long($this->request->getIpViaProxy() ?? ''),
                'user_agent'   => $this->request->getUserAgent(),
                'text'         => $fields['message'],
            ]
        );

        // TODO: Replace it
        $tools = di(Tools::class);
        $tools->recountForumTopic($topic->id);

        // Update user activity
        $userManager = di(UserManager::class);
        $userManager->incrementActivity($user, 'forum_posts');

        $this->markAsRead($topic->id, $user->id);

        return [
            'topic'   => $topic,
            'message' => $message,
        ];
    }
}
