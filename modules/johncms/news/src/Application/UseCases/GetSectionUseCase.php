<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;

final readonly class GetSectionUseCase
{
    public function __construct(
        private NewsSectionRepositoryInterface $sections,
    ) {
    }

    /**
     * @throws SectionNotFoundException
     */
    public function execute(int $id): NewsSection
    {
        return $this->sections->findById($id) ?? throw new SectionNotFoundException();
    }
}
