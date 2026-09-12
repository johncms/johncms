<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumSlugGenerator;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class EditForumSectionUseCase
{
    public function __construct(
        private ForumStructureRepositoryInterface $repository,
        private ForumSlugGenerator $slugGenerator,
    ) {
    }

    /**
     * A section cannot become its own parent, nor the child of one of its own descendants.
     */
    public function wouldCreateCycle(int $sectionId, int $newParentId): bool
    {
        $parent = $this->repository->find($newParentId);
        while ($parent !== null) {
            if ($parent->id === $sectionId) {
                return true;
            }
            $parent = $parent->parent ? $this->repository->find((int) $parent->parent) : null;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(ForumSection $section, array $data): void
    {
        $newParent = (int) $data['parent'];

        if ($newParent !== (int) $section->parent) {
            $data['sort'] = $this->repository->nextSort($newParent);
            ForumFile::query()
                ->where('cat', $section->parent)
                ->where('subcat', $section->id)
                ->update(['cat' => $newParent]);
        }

        $data['slug'] = $this->slugGenerator->generate((string) $data['name'], $newParent, $section->id);

        $section->update($data);
    }
}
