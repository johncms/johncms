<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final class ForumTopicRepository implements ForumTopicRepositoryInterface
{
    public function findById(int $topicId): ?ForumTopic
    {
        return ForumTopic::query()->find($topicId);
    }

    public function findByIdWithSection(int $topicId, bool $withFilesCount): ?ForumTopic
    {
        return ForumTopic::query()
            ->when(
                $withFilesCount,
                static fn($query) => $query->withCount('files')
            )
            ->with('section')
            ->find($topicId);
    }

    public function existsBySectionAndSlug(int $sectionId, string $slug, ?int $excludeTopicId = null): bool
    {
        return ForumTopic::query()
            ->where('section_id', $sectionId)
            ->where('slug', $slug)
            ->when(
                $excludeTopicId !== null,
                static fn($query) => $query->where('id', '!=', $excludeTopicId)
            )
            ->exists();
    }

    public function findActiveById(int $topicId): ?ForumTopic
    {
        return ForumTopic::query()
            ->where('id', $topicId)
            ->where(static function ($query): void {
                $query->whereNull('deleted')
                    ->orWhere('deleted', '!=', 1);
            })
            ->first();
    }

    public function countUnreadForUser(int $userId, bool $includeDeleted): int
    {
        return (int) ForumTopic::query()
            ->from('forum_topic as tpc')
            ->leftJoin('cms_forum_rdm as rdm', static function ($join) use ($userId): void {
                $join->on('tpc.id', '=', 'rdm.topic_id')
                    ->where('rdm.user_id', '=', $userId);
            })
            ->where(static function ($query): void {
                $query->whereNull('rdm.topic_id')
                    ->orWhereColumn('tpc.last_post_date', '>', 'rdm.time');
            })
            ->when(
                ! $includeDeleted,
                static function ($query): void {
                    $query->where(static function ($query): void {
                        $query->where('tpc.deleted', '!=', 1)
                            ->orWhereNull('tpc.deleted');
                    });
                }
            )
            ->toBase()
            ->count();
    }

    public function getUnreadForUser(int $userId, bool $includeDeleted, int $start, int $limit): array
    {
        return ForumTopic::query()
            ->from('forum_topic as tpc')
            ->leftJoin('cms_forum_rdm as rdm', static function ($join) use ($userId): void {
                $join->on('tpc.id', '=', 'rdm.topic_id')
                    ->where('rdm.user_id', '=', $userId);
            })
            ->leftJoin('forum_sections as rzd', 'rzd.id', '=', 'tpc.section_id')
            ->leftJoin('forum_sections as frm', 'frm.id', '=', 'rzd.parent')
            ->select([
                'tpc.*',
                'rzd.name as rzd_name',
                'frm.id as frm_id',
                'frm.name as frm_name',
            ])
            ->where(static function ($query): void {
                $query->whereNull('rdm.topic_id')
                    ->orWhereColumn('tpc.last_post_date', '>', 'rdm.time');
            })
            ->when(
                ! $includeDeleted,
                static function ($query): void {
                    $query->where(static function ($query): void {
                        $query->where('tpc.deleted', '!=', 1)
                            ->orWhereNull('tpc.deleted');
                    });
                }
            )
            ->orderByDesc('tpc.last_post_date')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->toBase()
            ->get()
            ->map(static fn(object $row): array => (array) $row)
            ->all();
    }

    public function countForPeriod(int $fromTime, bool $includeDeleted, bool $useModerationDate): int
    {
        $column = $useModerationDate ? 'tpc.mod_last_post_date' : 'tpc.last_post_date';

        return (int) ForumTopic::query()
            ->from('forum_topic as tpc')
            ->where($column, '>', $fromTime)
            ->when(
                ! $includeDeleted,
                static function ($query): void {
                    $query->where(static function ($query): void {
                        $query->where('tpc.deleted', '!=', 1)
                            ->orWhereNull('tpc.deleted');
                    });
                }
            )
            ->toBase()
            ->count();
    }

    public function getForPeriod(int $fromTime, bool $includeDeleted, bool $useModerationDate, int $start, int $limit): array
    {
        $column = $useModerationDate ? 'tpc.mod_last_post_date' : 'tpc.last_post_date';

        return ForumTopic::query()
            ->from('forum_topic as tpc')
            ->leftJoin('forum_sections as rzd', 'rzd.id', '=', 'tpc.section_id')
            ->leftJoin('forum_sections as frm', 'frm.id', '=', 'rzd.parent')
            ->select([
                'tpc.*',
                'rzd.name as rzd_name',
                'frm.name as frm_name',
            ])
            ->where($column, '>', $fromTime)
            ->when(
                ! $includeDeleted,
                static function ($query): void {
                    $query->where(static function ($query): void {
                        $query->where('tpc.deleted', '!=', 1)
                            ->orWhereNull('tpc.deleted');
                    });
                }
            )
            ->orderByDesc($column)
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->toBase()
            ->get()
            ->map(static fn(object $row): array => (array) $row)
            ->all();
    }

    public function getLatest(int $limit): array
    {
        return ForumTopic::query()
            ->from('forum_topic as tpc')
            ->leftJoin('forum_sections as rzd', 'rzd.id', '=', 'tpc.section_id')
            ->leftJoin('forum_sections as frm', 'frm.id', '=', 'rzd.parent')
            ->select([
                'tpc.*',
                'rzd.name as rzd_name',
                'frm.name as frm_name',
            ])
            ->where(static function ($query): void {
                $query->where('tpc.deleted', '!=', 1)
                    ->orWhereNull('tpc.deleted');
            })
            ->orderByDesc('tpc.last_post_date')
            ->limit(max(1, $limit))
            ->toBase()
            ->get()
            ->map(static fn(object $row): array => (array) $row)
            ->all();
    }

    public function countReadBySectionId(int $sectionId): int
    {
        return ForumTopic::query()
            ->read()
            ->where('section_id', $sectionId)
            ->count();
    }

    /**
     * @return Collection<int, ForumTopic>
     */
    public function getReadBySectionId(int $sectionId, int $limit, int $offset): Collection
    {
        return ForumTopic::query()
            ->read()
            ->where('section_id', $sectionId)
            ->orderByDesc('pinned')
            ->orderByDesc('last_post_date')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function markHasPoll(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['has_poll' => 1]);
    }

    public function clearHasPoll(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['has_poll' => null]);
    }

    public function setClosed(int $topicId, bool $closed): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['closed' => $closed]);
    }

    public function markDeleted(int $topicId, string $deletedBy): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['deleted' => true, 'deleted_by' => $deletedBy]);
    }

    public function deleteById(int $topicId): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->delete();
    }

    public function setPinned(int $topicId, bool $pinned): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['pinned' => $pinned ? 1 : null]);
    }

    public function restoreById(int $topicId, string $restoredBy): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['deleted' => null, 'deleted_by' => $restoredBy]);
    }

    public function save(ForumTopic $topic): void
    {
        $topic->save();
    }

    public function incrementViewCount(int $topicId): void
    {
        // The column is nullable, and a plain increment would keep NULL forever
        ForumTopic::query()
            ->where('id', $topicId)
            ->update(['view_count' => new Expression('COALESCE(`view_count`, 0) + 1')]);
    }

    public function updateStats(int $topicId, array $stats): void
    {
        ForumTopic::query()
            ->where('id', $topicId)
            ->update($stats);
    }

    public function getCursorForSitemap(): iterable
    {
        return ForumTopic::query()
            ->select(['id', 'section_id', 'slug', 'last_post_date'])
            ->orderBy('id')
            ->cursor();
    }
}
