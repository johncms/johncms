<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumSlugGenerator;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;

final readonly class AddForumSectionUseCase
{
    public function __construct(
        private ForumStructureRepositoryInterface $repository,
        private ForumSlugGenerator $slugGenerator,
    ) {
    }

    public function execute(int $parentId, string $name, string $description, int $access, int $sectionType): void
    {
        $this->repository->create([
            'parent'       => $parentId,
            'name'         => $name,
            'slug'         => $this->slugGenerator->generate($name, $parentId),
            'description'  => $description,
            'access'       => $access,
            'section_type' => $sectionType,
            'sort'         => $this->repository->nextSort($parentId),
        ]);
    }
}
