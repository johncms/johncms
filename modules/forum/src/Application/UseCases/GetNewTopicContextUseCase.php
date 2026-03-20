<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\NewTopicContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class GetNewTopicContextUseCase
{
    public function execute(int $sectionId): NewTopicContextDTO
    {
        $section = ForumSection::query()
            ->where('section_type', 1)
            ->where('id', $sectionId)
            ->first();

        if ($section === null) {
            throw new ForumNotFoundException('Section not found.');
        }

        return new NewTopicContextDTO($section);
    }
}
