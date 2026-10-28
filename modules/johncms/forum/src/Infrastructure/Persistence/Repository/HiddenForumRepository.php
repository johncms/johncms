<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Enums\HiddenListTab;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\HiddenForumRepositoryInterface;
use Johncms\Modules\Forum\Domain\ValueObjects\HiddenListFilter;
use Johncms\Users\User;

/**
 * The scopes that hide deleted topics, posts and files from a reader do not apply here: hidden
 * content is what this list is about.
 */
final class HiddenForumRepository implements HiddenForumRepositoryInterface
{
    public function count(HiddenListFilter $filter): int
    {
        return $this->query($filter)->count();
    }

    public function totals(): array
    {
        return [
            HiddenListTab::POSTS->value  => $this->query(new HiddenListFilter(HiddenListTab::POSTS))->count(),
            HiddenListTab::TOPICS->value => $this->query(new HiddenListFilter(HiddenListTab::TOPICS))->count(),
        ];
    }

    public function posts(HiddenListFilter $filter, int $limit, int $offset): Collection
    {
        return $this->postsQuery($filter)
            ->with([
                'topic' => static fn ($query) => $query->withoutGlobalScope('access'),
                'topic.section',
                'user',
            ])
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function topics(HiddenListFilter $filter, int $limit, int $offset): Collection
    {
        return $this->topicsQuery($filter)
            ->with('section')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function hiders(HiddenListTab $tab): array
    {
        return $this->query(new HiddenListFilter($tab))
            ->where('deleted_by', '!=', '')
            ->distinct()
            ->orderBy('deleted_by')
            ->pluck('deleted_by')
            ->all();
    }

    public function authorName(int $userId): ?string
    {
        return User::query()->whereKey($userId)->value('name');
    }

    public function topicName(int $topicId): ?string
    {
        return ForumTopic::query()->withoutGlobalScope('access')->whereKey($topicId)->value('name');
    }

    public function sectionName(int $sectionId): ?string
    {
        return ForumSection::query()->whereKey($sectionId)->value('name');
    }

    public function attachmentFilenames(HiddenListFilter $filter): array
    {
        $ids = $this->query($filter)->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        return ForumFile::query()
            ->withoutGlobalScope('access')
            ->whereIn($filter->tab === HiddenListTab::POSTS ? 'post' : 'topic', $ids)
            ->pluck('filename')
            ->all();
    }

    public function delete(HiddenListFilter $filter): void
    {
        $ids = $this->query($filter)->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        if ($filter->tab === HiddenListTab::POSTS) {
            ForumFile::query()->withoutGlobalScope('access')->whereIn('post', $ids)->delete();
            ForumMessage::query()->withoutGlobalScope('access')->whereIn('id', $ids)->delete();

            return;
        }

        ForumFile::query()->withoutGlobalScope('access')->whereIn('topic', $ids)->delete();
        ForumMessage::query()->withoutGlobalScope('access')->whereIn('topic_id', $ids)->delete();
        ForumTopic::query()->withoutGlobalScope('access')->whereIn('id', $ids)->delete();
    }

    /**
     * @return Builder<ForumMessage>|Builder<ForumTopic>
     */
    private function query(HiddenListFilter $filter): Builder
    {
        return $filter->tab === HiddenListTab::POSTS ? $this->postsQuery($filter) : $this->topicsQuery($filter);
    }

    /**
     * @return Builder<ForumMessage>
     */
    private function postsQuery(HiddenListFilter $filter): Builder
    {
        return ForumMessage::query()
            ->withoutGlobalScope('access')
            ->where('deleted', 1)
            ->when($filter->author !== null, static fn (Builder $query) => $query->where('user_id', $filter->author))
            ->when($filter->topic !== null, static fn (Builder $query) => $query->where('topic_id', $filter->topic))
            ->when($filter->hiddenBy !== null, static fn (Builder $query) => $query->where('deleted_by', $filter->hiddenBy))
            ->when($filter->search !== '', function (Builder $query) use ($filter): void {
                $like = $this->like($filter->search);
                $query->where(static function (Builder $search) use ($like): void {
                    $search->where('user_name', 'like', $like)
                        ->orWhereHas('topic', static fn (Builder $topic) => $topic->withoutGlobalScope('access')->where('name', 'like', $like));
                });
            });
    }

    /**
     * @return Builder<ForumTopic>
     */
    private function topicsQuery(HiddenListFilter $filter): Builder
    {
        return ForumTopic::query()
            ->withoutGlobalScope('access')
            ->where('deleted', 1)
            ->when($filter->author !== null, static fn (Builder $query) => $query->where('user_id', $filter->author))
            ->when($filter->section !== null, static fn (Builder $query) => $query->where('section_id', $filter->section))
            ->when($filter->hiddenBy !== null, static fn (Builder $query) => $query->where('deleted_by', $filter->hiddenBy))
            ->when($filter->search !== '', function (Builder $query) use ($filter): void {
                $like = $this->like($filter->search);
                $query->where(static fn (Builder $search) => $search->where('name', 'like', $like)->orWhere('user_name', 'like', $like));
            });
    }

    private function like(string $search): string
    {
        return '%' . addcslashes($search, '%_\\') . '%';
    }
}
