<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Query\ForumFileCountQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileListingQuery;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;

final class ForumFileRepository implements ForumFileRepositoryInterface
{
    public function save(ForumFile $file): void
    {
        $file->save();
    }

    public function countAll(): int
    {
        return ForumFile::query()->count();
    }

    public function getByTopicId(int $topicId): array
    {
        return ForumFile::query()
            ->where('topic', $topicId)
            ->get()
            ->all();
    }

    public function hasFilesForPost(int $postId): bool
    {
        return ForumFile::query()
            ->where('post', $postId)
            ->exists();
    }

    public function findById(int $fileId): ?ForumFile
    {
        return ForumFile::query()->find($fileId);
    }

    public function findByIdAndPostId(int $fileId, int $postId): ?ForumFile
    {
        return ForumFile::query()
            ->where('id', $fileId)
            ->where('post', $postId)
            ->first();
    }

    public function deleteById(int $fileId): void
    {
        ForumFile::query()
            ->where('id', $fileId)
            ->delete();
    }

    public function deleteByPostId(int $postId): void
    {
        ForumFile::query()
            ->where('post', $postId)
            ->delete();
    }

    public function markDeletedByPostId(int $postId): void
    {
        ForumFile::query()
            ->where('post', $postId)
            ->update(['del' => 1]);
    }

    public function restoreByPostId(int $postId): void
    {
        ForumFile::query()
            ->where('post', $postId)
            ->update(['del' => 0]);
    }

    public function getByPostId(int $postId): array
    {
        return ForumFile::query()
            ->where('post', $postId)
            ->get()
            ->all();
    }

    public function markDeletedByTopicId(int $topicId): void
    {
        ForumFile::query()
            ->where('topic', $topicId)
            ->update(['del' => 1]);
    }

    public function deleteByTopicId(int $topicId): void
    {
        ForumFile::query()
            ->where('topic', $topicId)
            ->delete();
    }

    public function countForListing(ForumFileCountQuery $query): int
    {
        return $this->countByQuery($query);
    }

    public function getListingItems(ForumFileListingQuery $query): array
    {
        $comparison = $query->upfp ? '>=' : '<=';
        $start = max(0, $query->start);
        $limit = max(1, $query->limit);

        return ForumFile::query()
            ->from('cms_forum_files as files')
            ->when(
                $query->filter->isNew,
                static fn($builder) => $builder->where('files.time', '>', $query->filter->newFrom),
                static fn($builder) => $builder->where('files.filetype', $query->filter->fileType),
            )
            ->when(
                ! $query->filter->scope->includeDeleted,
                static fn($builder) => $builder->where('files.del', '!=', 1),
            )
            ->when(
                $query->filter->scope->categoryId !== null,
                static fn($builder) => $builder->where('files.cat', $query->filter->scope->categoryId),
            )
            ->when(
                $query->filter->scope->categoryId === null && $query->filter->scope->sectionId !== null,
                static fn($builder) => $builder->where('files.subcat', $query->filter->scope->sectionId),
            )
            ->when(
                $query->filter->scope->categoryId === null
                    && $query->filter->scope->sectionId === null
                    && $query->filter->scope->topicId !== null,
                static fn($builder) => $builder->where('files.topic', $query->filter->scope->topicId),
            )
            ->join('forum_messages as mess', 'files.post', '=', 'mess.id')
            ->join('users as u', 'u.id', '=', 'mess.user_id')
            ->select([
                'files.*',
                'mess.user_id',
                'mess.text',
                'u.name',
                'u.lastdate',
                'u.status',
            ])
            ->selectSub(
                static function ($subQuery) use ($comparison): void {
                    $subQuery
                        ->from('forum_messages')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('topic_id', 'files.topic')
                        ->whereColumn('id', $comparison, 'files.post');
                },
                'page'
            )
            ->orderByDesc('files.time')
            ->offset($start)
            ->limit($limit)
            ->toBase()
            ->get()
            ->map(static fn(object $row): array => (array) $row)
            ->all();
    }

    public function countByQuery(ForumFileCountQuery $query): int
    {
        return (int) ForumFile::query()
            ->from('cms_forum_files as files')
            ->when(
                $query->isNew,
                static fn($builder) => $builder->where('files.time', '>', $query->newFrom),
                static fn($builder) => $builder->where('files.filetype', $query->fileType),
            )
            ->when(
                ! $query->scope->includeDeleted,
                static fn($builder) => $builder->where('files.del', '!=', 1),
            )
            ->when(
                $query->scope->categoryId !== null,
                static fn($builder) => $builder->where('files.cat', $query->scope->categoryId),
            )
            ->when(
                $query->scope->categoryId === null && $query->scope->sectionId !== null,
                static fn($builder) => $builder->where('files.subcat', $query->scope->sectionId),
            )
            ->when(
                $query->scope->categoryId === null
                    && $query->scope->sectionId === null
                    && $query->scope->topicId !== null,
                static fn($builder) => $builder->where('files.topic', $query->scope->topicId),
            )
            ->toBase()
            ->count();
    }
}
