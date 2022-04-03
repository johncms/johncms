<?php

declare(strict_types=1);

namespace Johncms\Forum\Topics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Users\User;

class ForumTopicRepository
{
    public ?User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
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
            ->orderBy('last_post_date');
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
}
