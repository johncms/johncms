<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumUnread;
use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Models\ForumVoteUser;

final class EloquentForumStructureRepository implements ForumStructureRepositoryInterface
{
    public function categories(): Collection
    {
        return ForumSection::query()
            ->where('parent', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->withCount('subsections')
            ->get();
    }

    public function subsections(int $parentId): Collection
    {
        return ForumSection::query()
            ->where('parent', $parentId)
            ->orderBy('sort')
            ->withCount('subsections')
            ->get();
    }

    public function find(int $id): ?ForumSection
    {
        return ForumSection::query()->find($id);
    }

    public function nextSort(int $parentId): int
    {
        return (int) ForumSection::query()->where('parent', $parentId)->max('sort') + 1;
    }

    public function create(array $attributes): void
    {
        ForumSection::query()->create($attributes);
    }

    public function slugExists(string $slug, int $parentId, ?int $excludeId): bool
    {
        return ForumSection::query()
            ->where('parent', $parentId)
            ->where('slug', $slug)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }

    public function countChildSections(int $id): int
    {
        return ForumSection::query()->where('parent', $id)->count();
    }

    public function countTopics(int $sectionId): int
    {
        return ForumTopic::query()->where('section_id', $sectionId)->count();
    }

    public function categoriesForMove(int $excludeId): Collection
    {
        return ForumSection::query()
            ->where(fn ($query) => $query->where('section_type', '!=', 1)->orWhereNull('section_type'))
            ->where('id', '!=', $excludeId)
            ->orderBy('sort')
            ->get();
    }

    public function sectionsForMove(int $parentRef, int $excludeId): Collection
    {
        return ForumSection::query()
            ->where('parent', $parentRef)
            ->where('id', '!=', $excludeId)
            ->orderBy('sort')
            ->get();
    }

    public function topLevelExcept(int $excludeId): Collection
    {
        return ForumSection::query()
            ->where('parent', 0)
            ->where('id', '!=', $excludeId)
            ->orderBy('sort')
            ->get();
    }

    public function moveSubsectionsToCategory(int $fromCategoryId, int $toCategoryId): void
    {
        $sort = (int) ForumSection::query()->where('parent', $toCategoryId)->max('sort');

        $children = ForumSection::query()->where('parent', $fromCategoryId)->get();
        foreach ($children as $child) {
            $child->parent = $toCategoryId;
            $child->sort = ++$sort;
            $child->save();
        }

        ForumFile::query()->where('cat', $fromCategoryId)->update(['cat' => $toCategoryId]);
        $this->deleteSection($fromCategoryId);
    }

    public function moveTopicsToSection(int $fromSectionId, int $toSectionId): void
    {
        ForumTopic::query()->where('section_id', $fromSectionId)->update(['section_id' => $toSectionId]);
        ForumFile::query()->where('subcat', $fromSectionId)->update(['subcat' => $toSectionId]);
        $this->deleteSection($fromSectionId);
    }

    public function deleteSection(int $id): void
    {
        ForumSection::query()->where('id', $id)->delete();
    }

    public function attachedFilenames(int $sectionId): array
    {
        return ForumFile::query()->where('subcat', $sectionId)->pluck('filename')->all();
    }

    public function deleteSectionCascade(int $id): void
    {
        ForumFile::query()->where('subcat', $id)->delete();

        $topicIds = ForumTopic::query()->where('section_id', $id)->pluck('id')->all();
        if ($topicIds !== []) {
            ForumMessage::query()->whereIn('topic_id', $topicIds)->delete();
            ForumVote::query()->whereIn('topic', $topicIds)->delete();
            ForumVoteUser::query()->whereIn('topic', $topicIds)->delete();
            ForumUnread::query()->whereIn('topic_id', $topicIds)->delete();
        }

        ForumTopic::query()->where('section_id', $id)->delete();
        $this->deleteSection($id);
    }
}
