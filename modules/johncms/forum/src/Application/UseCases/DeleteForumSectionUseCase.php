<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\SectionDeletionContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\SectionDeletionException;
use Johncms\Modules\Forum\Application\Services\ForumTreeOptions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;

/**
 * Deletes a section of the forum.
 *
 * An empty section goes at once. The sections of a full one go to the end of a section that holds
 * sections, or to the root; its topics go to another section of topics, or are deleted with it,
 * posts and files included, by whoever may destroy the structure.
 */
final readonly class DeleteForumSectionUseCase
{
    public function __construct(
        private ForumStructureRepositoryInterface $repository,
        private ForumTreeOptions $treeOptions,
    ) {
    }

    public function context(ForumSection $section): SectionDeletionContextDTO
    {
        $holdsTopics = (int) $section->section_type === ForumTreeOptions::TYPE_TOPICS;
        $sections = $this->repository->countChildSections($section->id);
        $topics = $this->repository->countTopics($section->id);

        $targets = [];
        if ($holdsTopics && $topics > 0) {
            $targets = $this->treeOptions->topicHolders($this->repository->tree(), $section->id);
        } elseif (! $holdsTopics && $sections > 0) {
            $targets = $this->treeOptions->sectionHolders($this->repository->tree(), $section->id);
        }

        return new SectionDeletionContextDTO($section, $holdsTopics, $sections, $topics, $targets);
    }

    /**
     * The rows go here; the names of the files that went with the topics are handed back, for the
     * caller to take them off the disk.
     *
     * @param int|null $target  Where what the section holds goes; null when it is deleted with it.
     * @param bool     $destroy Delete the topics with the section instead of moving them.
     * @return list<string>
     * @throws SectionDeletionException
     */
    public function execute(ForumSection $section, ?int $target, bool $destroy, bool $mayDestroy): array
    {
        $context = $this->context($section);

        if ($context->isEmpty()) {
            $this->repository->deleteSection($section->id);

            return [];
        }

        if ($context->holdsTopics && $destroy) {
            if (! $mayDestroy) {
                throw new SectionDeletionException(__('Only the owner of the structure deletes topics with their section'));
            }

            $filenames = $this->repository->attachedFilenames($section->id);
            $this->repository->deleteSectionCascade($section->id);

            return $filenames;
        }

        if ($target === null || ! in_array($target, array_column($context->targets, 'id'), true)) {
            throw new SectionDeletionException(__('Choose where what the section holds goes'));
        }

        if ($context->holdsTopics) {
            $this->repository->moveTopicsToSection($section->id, $target);
        } else {
            $this->repository->moveSubsectionsToCategory($section->id, $target);
        }

        return [];
    }
}
